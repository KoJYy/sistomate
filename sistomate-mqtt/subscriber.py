#!/usr/bin/env python3
"""subscriber.py - terima data SISTOMATE dari broker MQTT lokal, tulis ke MySQL.

TIKET-MQTT-01 Task 2. Aturan:
- paho-mqtt 2.x (CallbackAPIVersion.VERSION2), client id tetap, clean_session=False,
  QoS1, manual_ack: PUBACK hanya dikirim SETELAH insert sukses.
- Validasi payload sama seperti sistomate-firmware/ingest.php:
    plant_status ∈ {sehat, kering, layu, sakit}; status aktuator ∈ {ON, OFF};
    id_device diambil dari topik (bukan payload); actuator_name dipotong 100 char.
- Dedup msg_id pakai LRU di memori (1000 entri) supaya publish ulang QoS1
  tidak menggandakan baris.
- Galat DB transien (mysqld mati, DB salah, koneksi putus): pesan TIDAK di-ack,
  koneksi MQTT diputus -> broker meredeliver setelah sesi tersambung lagi.
- Galat DB permanen (FK, data salah): di-ack tanpa insert supaya tidak loop.

Env (dibaca dari environment process; lihat systemd/sistomate-mqtt-sub.service):
  MQTT_HOST, MQTT_PORT, MQTT_USER_SUB, MQTT_PASS_SUB   (dari sistomate-creds.env)
  MQTT_DB_HOST, MQTT_DB_PORT, MQTT_DB_NAME, MQTT_DB_USER, MQTT_DB_PASS
  MQTT_CLIENT_ID (default: sistomate-sub)
"""
import json
import logging
import os
import signal
import sys
import threading
import time
from collections import OrderedDict

import paho.mqtt.client as mqtt
import pymysql

logging.basicConfig(
    level=logging.INFO,
    stream=sys.stdout,
    format="%(asctime)s %(levelname)s %(message)s",
)
log = logging.getLogger("subscriber")

# Whitelist identik dengan ingest.php.
# 3 status (keputusan Owner 21 Nov 2026): "kering" dihapus, tanah kering = "layu".
VALID_STATUS = ("sehat", "layu", "sakit")
VALID_ONOFF = ("ON", "OFF")
VALID_KINDS = ("sensor", "actuator")
TOPIC_ROOT = "sistomate"

DEDUP_MAX = 1000
BACKOFF_START_S = 2.0
BACKOFF_MAX_S = 60.0


class DbDown(Exception):
    """Koneksi/insert MySQL gagal sementara: pesan harus di-redeliver (tanpa ack)."""


def env(name, default=None):
    val = os.environ.get(name, default)
    if val is None:
        log.error("env %s wajib diisi", name)
        sys.exit(2)
    return val


def _f(val):
    """Coerce float ala PHP (float)'abc' -> 0.0."""
    try:
        return float(val)
    except (TypeError, ValueError):
        return 0.0


def _i(val):
    """Coerce int ala PHP (int)'abc' -> 0."""
    try:
        return int(float(val))
    except (TypeError, ValueError):
        return 0


class Database:
    """Koneksi MySQL longgar: connect saat dibutuhkan, reconnect otomatis."""

    def __init__(self):
        self.host = env("MQTT_DB_HOST", "127.0.0.1")
        self.port = int(env("MQTT_DB_PORT", "3306"))
        self.name = env("MQTT_DB_NAME")
        self.user = env("MQTT_DB_USER")
        self.password = env("MQTT_DB_PASS")
        self.conn = None

    def _connect(self):
        self.close()
        self.conn = pymysql.connect(
            host=self.host,
            port=self.port,
            user=self.user,
            password=self.password,
            database=self.name,
            charset="utf8mb4",
            connect_timeout=5,
            read_timeout=10,
            write_timeout=10,
            autocommit=True,
        )
        log.info("MySQL tersambung ke %s@%s:%s/%s", self.user, self.host, self.port, self.name)

    def close(self):
        if self.conn is not None:
            try:
                self.conn.close()
            except pymysql.err.MySQLError:
                pass
            self.conn = None

    def insert(self, kind, row):
        """Insert satu baris. Transien -> DbDown; permanen (FK/dll) -> raise asli."""
        try:
            if self.conn is None:
                self._connect()
            with self.conn.cursor() as cur:
                if kind == "sensor":
                    cur.execute(
                        "INSERT INTO sensor_logs "
                        "(recorded_at, air_temperature, air_humidity, soil_moisture, "
                        " rgb_red, rgb_green, rgb_blue, plant_status, id_device) "
                        "VALUES (NOW(), %s, %s, %s, %s, %s, %s, %s, %s)",
                        row,
                    )
                else:
                    cur.execute(
                        "INSERT INTO actuator_logs "
                        "(actuator_name, status, executed_at, id_device) "
                        "VALUES (%s, %s, NOW(), %s)",
                        row,
                    )
        except (pymysql.err.OperationalError, pymysql.err.InterfaceError) as exc:
            # Server mati / koneksi putus / database tak dikenal -> transien.
            self.close()
            raise DbDown(str(exc)) from exc


class Subscriber:
    def __init__(self):
        self.client = mqtt.Client(
            mqtt.CallbackAPIVersion.VERSION2,
            client_id=env("MQTT_CLIENT_ID", "sistomate-sub"),
            clean_session=False,
            manual_ack=True,
        )
        self.client.on_connect = self.on_connect
        self.client.on_message = self.on_message
        self.client.on_disconnect = self.on_disconnect
        self.client.username_pw_set(env("MQTT_USER_SUB"), env("MQTT_PASS_SUB"))
        self.db = Database()
        self.seen = OrderedDict()  # msg_id (unik per device) -> True, LRU
        self.backoff = BACKOFF_START_S
        self.timer = None
        self.stopping = False

    # --- callback broker -----------------------------------------------
    def on_connect(self, client, userdata, flags, reason_code, properties=None):
        if reason_code.is_failure:
            log.error("CONNECT ditolak broker: %s", reason_code)
            return
        self.backoff = BACKOFF_START_S
        # clean_session=False: langganan ikut sesi tersimpan, subscribe ulang
        # tetap dilakukan supaya aman kalau sesi broker hilang.
        client.subscribe(f"{TOPIC_ROOT}/+/sensor", qos=1)
        client.subscribe(f"{TOPIC_ROOT}/+/actuator", qos=1)
        log.info("TERHUBUNG ke broker (sesi persisten, QoS1, manual_ack); subscribe sensor+aktuator")

    def on_disconnect(self, client, userdata, disconnect_flags, reason_code, properties=None):
        log.warning("putus dari broker: %s", reason_code)

    def on_message(self, client, userdata, msg):
        try:
            keep_ack = self.handle(msg)
        except DbDown as exc:
            log.error(
                "DB gagal (%s); mid=%s TIDAK di-ack, putus utk redelivery", exc, msg.mid
            )
            keep_ack = False
        except pymysql.err.MySQLError as exc:
            # Galat permanen (FK device tak ada, data melebihi kolom, dst).
            log.error("galat DB permanen, di-ack tanpa insert: %s", exc)
            keep_ack = True
        except Exception:
            log.exception("kesalahan tak terduga, di-ack supaya tidak loop; mid=%s", msg.mid)
            keep_ack = True

        if keep_ack:
            client.ack(msg.mid, qos=msg.qos)
        else:
            self._drop_and_retry()

    # --- pemrosesan pesan ----------------------------------------------
    def handle(self, msg):
        """True = boleh di-ack. Raise DbDown = jangan di-ack."""
        parts = msg.topic.split("/")
        if len(parts) != 3 or parts[0] != TOPIC_ROOT:
            log.warning("topik asing %s - di-ack tanpa insert", msg.topic)
            return True
        try:
            device_id = int(parts[1])
        except ValueError:
            log.warning("id_device non-numerik di topik %s - di-ack", msg.topic)
            return True
        kind = parts[2]
        if kind not in VALID_KINDS:
            log.warning("jenis pesan %r tidak dikenal - di-ack", kind)
            return True

        try:
            payload = json.loads(msg.payload.decode("utf-8"))
        except (UnicodeDecodeError, json.JSONDecodeError):
            log.warning("payload bukan JSON valid di %s - di-ack", msg.topic)
            return True
        if not isinstance(payload, dict):
            log.warning("payload bukan objek JSON di %s - di-ack", msg.topic)
            return True
        msg_id = payload.get("msg_id")

        # --- validasi identik ingest.php ---
        if kind == "sensor":
            status = payload.get("plant_status", "")
            if status == "kering": status = "layu"  # legacy firmware pra-flash 21 Nov
            if status not in VALID_STATUS:
                log.warning(
                    "plant_status tidak valid (%r) topic=%s msg_id=%s - di-ack tanpa insert",
                    status, msg.topic, msg_id,
                )
                return True
            row = (
                _f(payload.get("air_temperature")),
                _f(payload.get("air_humidity")),
                _f(payload.get("soil_moisture")),
                _i(payload.get("rgb_red")),
                _i(payload.get("rgb_green")),
                _i(payload.get("rgb_blue")),
                status,
                device_id,
            )
        else:
            st = payload.get("status", "")
            if st not in VALID_ONOFF:
                log.warning(
                    "status aktuator tidak valid (%r) topic=%s msg_id=%s - di-ack tanpa insert",
                    st, msg.topic, msg_id,
                )
                return True
            name = str(payload.get("actuator_name", ""))[:100]
            row = (name, st, device_id)

        # --- dedup msg_id (LRU) ---
        key = f"{device_id}:{msg_id}" if msg_id is not None else None
        if key is not None and key in self.seen:
            log.info("duplikat msg_id=%s - di-ack tanpa insert", msg_id)
            return True

        self.db.insert(kind, row)  # bisa raise DbDown

        if key is not None:
            self.seen[key] = True
            while len(self.seen) > DEDUP_MAX:
                self.seen.popitem(last=False)
        log.info("INSERT %s device=%s msg_id=%s", kind, device_id, msg_id)
        return True

    # --- putus + redelivery saat DB mati --------------------------------
    def _drop_and_retry(self):
        if self.stopping:
            return
        try:
            self.client.disconnect()
        except Exception:
            pass
        try:
            self.client.loop_stop()
        except Exception:
            pass
        delay = self.backoff
        self.backoff = min(self.backoff * 2, BACKOFF_MAX_S)
        log.info("coba sambung lagi dalam %.0f dtk", delay)
        self._schedule(delay, self._reconnect)

    def _reconnect(self):
        if self.stopping:
            return
        try:
            self.client.reconnect()
        except Exception as exc:
            log.error("reconnect gagal: %s", exc)
            self._schedule(self.backoff, self._reconnect)
            self.backoff = min(self.backoff * 2, BACKOFF_MAX_S)
            return
        rc = self.client.loop_start()
        if rc == mqtt.MQTT_ERR_INVAL:
            # Thread lama belum selesai; coba lagi sebentar lagi.
            self._schedule(0.5, self._reconnect)
        elif rc != mqtt.MQTT_ERR_SUCCESS:
            log.error("loop_start gagal rc=%s", rc)
            self._schedule(self.backoff, self._reconnect)

    def _schedule(self, delay, fn):
        if self.timer is not None:
            self.timer.cancel()
        self.timer = threading.Timer(delay, fn)
        self.timer.daemon = True
        self.timer.start()

    # --- lifecycle -------------------------------------------------------
    def start(self):
        host = env("MQTT_HOST", "127.0.0.1")
        port = int(env("MQTT_PORT", "1884"))
        self.client.connect(host, port, keepalive=60)
        self.client.loop_start()
        log.info(
            "subscriber start: broker %s:%s client_id=%s db=%s@%s/%s",
            host, port, self.client._client_id.decode(),
            self.db.user, self.db.host, self.db.name,
        )

    def stop(self, *_):
        self.stopping = True
        if self.timer is not None:
            self.timer.cancel()
        try:
            self.client.disconnect()
        except Exception:
            pass
        try:
            self.client.loop_stop()
        except Exception:
            pass
        self.db.close()
        log.info("subscriber berhenti")
        sys.exit(0)


def main():
    sub = Subscriber()
    signal.signal(signal.SIGTERM, sub.stop)
    signal.signal(signal.SIGINT, sub.stop)
    sub.start()
    # Parkir thread utama; semua kerja di thread paho + timer.
    while True:
        time.sleep(3600)


if __name__ == "__main__":
    main()
