#!/usr/bin/env python3
"""config_publisher.py - terbitkan config web (tabel `configs`) ke MQTT retained.

Alur: halaman Pengaturan menulis tabel `configs`; daemon ini memantau perubahan
dan publish `sistomate/<id_device>/config` (QoS1, retain) supaya firmware
langsung memakai ambang terbaru tanpa perlu HTTP.

Env (dibaca dari environment process; lihat systemd/sistomate-config-pub.service):
  MQTT_HOST, MQTT_PORT, MQTT_USER_PUB, MQTT_PASS_PUB   (dari sistomate-creds.env)
  MQTT_DB_HOST, MQTT_DB_PORT, MQTT_DB_NAME, MQTT_DB_USER, MQTT_DB_PASS
  CONFIG_POLL_SECONDS (default 2), CONFIG_TOPIC_PREFIX (default sistomate),
  MQTT_CLIENT_ID (default sistomate-config-publisher)

Mode:
  --once     publish semua config sekali lalu keluar (untuk uji/manual sync)
  (default)  daemon: poll DB tiap CONFIG_POLL_SECONDS, publish hanya jika berubah
"""
import argparse
import json
import logging
import os
import signal
import sys
import threading
import time

import paho.mqtt.client as mqtt
import pymysql

logging.basicConfig(
    level=logging.INFO,
    stream=sys.stdout,
    format="%(asctime)s %(levelname)s %(message)s",
)
log = logging.getLogger("config-publisher")


def env(name, default=None):
    val = os.environ.get(name, default)
    if val is None or val == "":
        if default is None:
            log.error("env %s wajib diisi", name)
            sys.exit(2)
        return default
    return val


def number(val):
    """Normalisasi DECIMAL MySQL -> int kalau bulat, else float."""
    f = float(val)
    return int(f) if f.is_integer() else f


def payload_for(row):
    return json.dumps(
        {
            "soil_on": number(row[1]),
            "soil_off": number(row[2]),
            "temp_on": number(row[3]),
            "temp_off": number(row[4]),
            "fert_duration_s": int(row[5]),
        },
        separators=(",", ":"),
    )


class Database:
    def __init__(self):
        self.host = env("MQTT_DB_HOST", "127.0.0.1")
        self.port = int(env("MQTT_DB_PORT", "3306"))
        self.name = env("MQTT_DB_NAME")
        self.user = env("MQTT_DB_USER")
        self.password = env("MQTT_DB_PASS")
        self.conn = None

    def fetch(self):
        if self.conn is None:
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
        with self.conn.cursor() as cur:
            cur.execute(
                "SELECT id_device, soil_on, soil_off, temp_on, temp_off, fert_duration_s "
                "FROM configs ORDER BY id_device"
            )
            return cur.fetchall()

    def close(self):
        if self.conn is not None:
            try:
                self.conn.close()
            except pymysql.err.MySQLError:
                pass
            self.conn = None


class Publisher:
    def __init__(self):
        self.host = env("MQTT_HOST", "127.0.0.1")
        self.port = int(env("MQTT_PORT", "1884"))
        self.prefix = env("CONFIG_TOPIC_PREFIX", "sistomate")
        self.db = Database()
        self.client = mqtt.Client(
            mqtt.CallbackAPIVersion.VERSION2,
            client_id=env("MQTT_CLIENT_ID", "sistomate-config-publisher"),
        )
        user = os.environ.get("MQTT_USER_PUB", "")
        if user:
            self.client.username_pw_set(user, os.environ.get("MQTT_PASS_PUB", ""))
        self.client.on_connect = self.on_connect
        self.client.on_disconnect = self.on_disconnect
        self.connected = threading.Event()

    def on_connect(self, client, userdata, flags, reason_code, properties=None):
        if reason_code.is_failure:
            log.error("CONNECT ditolak broker: %s", reason_code)
            return
        log.info("TERHUBUNG ke broker %s:%s", self.host, self.port)
        self.connected.set()

    def on_disconnect(self, client, userdata, disconnect_flags, reason_code, properties=None):
        self.connected.clear()
        log.warning("putus dari broker: %s", reason_code)

    def start(self):
        self.client.connect(self.host, self.port, keepalive=30)
        self.client.loop_start()
        if not self.connected.wait(10):
            raise RuntimeError("tidak terhubung ke broker dalam 10 detik")

    def stop(self):
        try:
            self.client.disconnect()
        finally:
            self.client.loop_stop()
            self.db.close()

    def publish_changed(self, last):
        """Publish config yang payload-nya berubah sejak publish terakhir.

        `last` = dict id_device -> payload JSON terakhir yang sukses terbit.
        Return jumlah publish yang sukses.
        """
        rows = self.db.fetch()
        count = 0
        for row in rows:
            device_id = int(row[0])
            payload = payload_for(row)
            if last.get(device_id) == payload:
                continue
            topic = f"{self.prefix}/{device_id}/config"
            info = self.client.publish(topic, payload, qos=1, retain=True)
            try:
                info.wait_for_publish(timeout=5)
            except (ValueError, RuntimeError) as exc:
                log.error("publish gagal %s: %s", topic, exc)
                continue
            if not info.is_published():
                log.error("publish belum terkirim %s (rc=%s)", topic, info.rc)
                continue
            last[device_id] = payload
            count += 1
            log.info("PUBLISH %s %s", topic, payload)
        return count

    def publish_all(self):
        return self.publish_changed({})


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--once", action="store_true", help="publish semua config sekali lalu keluar")
    args = parser.parse_args()

    pub = Publisher()
    stop = threading.Event()

    def handle_signal(signum, _frame):
        log.info("sinyal %s diterima, berhenti", signum)
        stop.set()

    signal.signal(signal.SIGTERM, handle_signal)
    signal.signal(signal.SIGINT, handle_signal)

    last = {}
    try:
        pub.start()
        if args.once:
            pub.publish_all()
            return 0

        interval = float(env("CONFIG_POLL_SECONDS", "2"))
        log.info("daemon poll tiap %.1f detik", interval)
        try:
            pub.publish_changed(last)
        except pymysql.err.MySQLError as exc:
            log.error("DB error saat publish awal: %s (lanjut poll)", exc)
            pub.db.close()

        while not stop.wait(interval):
            try:
                pub.publish_changed(last)
            except pymysql.err.MySQLError as exc:
                log.error("DB error saat poll: %s (reconnect)", exc)
                pub.db.close()
            except Exception:
                log.exception("poll gagal; lanjut")
        return 0
    finally:
        pub.stop()


if __name__ == "__main__":
    sys.exit(main())
