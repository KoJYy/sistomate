# sistomate-mqtt: Broker & jalur MQTT SISTOMATE (lokal)

> TIKET-MQTT-01 rev3. Instance Mosquitto **terpisah** dari broker PingPong.
> PingPong memakai `/etc/mosquitto/mosquitto.conf` (port 1883, Node-RED).
> File di repo ini **tidak pernah** menyentuh atau me-restart service itu.

## Komponen (Task 1)

| Berkas repo | Berkas sistem | Peran |
|---|---|---|
| `mosquitto-sistomate.conf.example` | `/etc/mosquitto/sistomate.conf` | listener 1884 (TCP auth) + 9002 (WS read-only) |
| `acl.example` | `/etc/mosquitto/sistomate.acl` | ACL per-user listener 1884 |
| `acl-ws.example` | `/etc/mosquitto/sistomate-ws.acl` | ACL read-only listener 9002 |
| `systemd/mosquitto-sistomate.service` | `/etc/systemd/system/mosquitto-sistomate.service` | unit instance terpisah |

## Komponen (Task 2)

| Berkas repo | Berkas sistem | Peran |
|---|---|---|
| `subscriber.py` | (dijalankan unit) | subscriber paho 2.1 QoS1 `manual_ack` -> MySQL |
| `systemd/sistomate-mqtt-sub.service` | `/etc/systemd/system/sistomate-mqtt-sub.service` | unit subscriber, `enabled` |

Berkas sistem subscriber (TIDAK ada di repo):

- `/etc/sistomate-mqtt-sub.env`: target DB (`MQTT_DB_*`, root 600). Saat ini
  menunjuk **`sistomate`** (produksi); `tools/test-mqtt-local.sh` sementara
  mengarahkannya ke `sistomate_webtest` lalu memulihkan.
- Kredensial broker dipakai ulang dari `/etc/mosquitto/sistomate-creds.env`.

Perilaku `subscriber.py`:

- Validasi identik `ingest.php`: `plant_status` ∈ {sehat, layu, sakit}
  (legacy `kering` dipetakan ke `layu`), `status` aktuator ∈ {ON, OFF},
  `id_device` dari topik, `actuator_name` potong 100.
- Dedup `msg_id` via LRU 1000 entri di memori (publish ulang QoS1 tidak menggandakan baris).
- **Ack hanya setelah insert sukses.** Galat DB transien (mysqld mati/DB salah):
  pesan tidak di-ack -> putus -> backoff 2-60 dtk -> reconnect -> broker meredeliver.
- Galat permanen (FK/data salah): di-ack tanpa insert supaya tidak loop.
- Log ke stdout -> journald: `journalctl -u sistomate-mqtt-sub -f`.

## Komponen (opsi B: config web -> firmware)

| Berkas repo | Berkas sistem | Peran |
|---|---|---|
| `config_publisher.py` | (dijalankan unit) | poll tabel `configs`, publish retained `sistomate/<id>/config` QoS1 |
| `systemd/sistomate-config-pub.service` | `/etc/systemd/system/sistomate-config-pub.service` | unit publisher, `enabled` |

Alur: halaman **Pengaturan** menulis `configs` -> publisher poll tiap
`CONFIG_POLL_SECONDS` (default 2 dtk) -> publish retained -> firmware yang
subscribe `sistomate/<id>/config` langsung memakai ambang baru. Saat broker
putus, firmware jatuh ke HTTP dan tetap menerima config dari respons
`ingest.php`.

Install:

```bash
sudo install -o root -g root -m 644 systemd/sistomate-config-pub.service /etc/systemd/system/
sudo systemctl daemon-reload
sudo systemctl enable --now sistomate-config-pub
journalctl -u sistomate-config-pub -f
```

Uji: `bash tools/test-config-publisher.sh` (23 assertion; broker uji 1886 +
DB uji `sistomate_webtest`, tidak menyentuh produksi).

Berkas sistem lain (TIDAK ada di repo):

- `/etc/mosquitto/sistomate.passwd`: hash 3 user (`esp32-1`, `subscriber`, `publisher`), dibuat `mosquitto_passwd`.
- `/etc/mosquitto/sistomate-creds.env`: kredensial plaintext untuk firmware lokal & tool (root, 600).
- `/var/lib/mosquitto-sistomate/`: persistence terpisah.

Semua file broker dimiliki `mosquitto:mosquitto` mode 640 (versi depan mosquitto
menolak load file yang ownernya bukan mosquitto).

## Install

```bash
sudo install -o root -g mosquitto -m 640 mosquitto-sistomate.conf.example /etc/mosquitto/sistomate.conf
sudo install -o root -g mosquitto -m 640 acl.example    /etc/mosquitto/sistomate.acl
sudo install -o root -g mosquitto -m 640 acl-ws.example /etc/mosquitto/sistomate-ws.acl
sudo mosquitto_passwd -c /etc/mosquitto/sistomate.passwd esp32-1   # lalu -b utk subscriber, publisher
sudo chown mosquitto:mosquitto /etc/mosquitto/sistomate.{conf,acl,pass*} 2>/dev/null
sudo install -d -o mosquitto -g mosquitto /var/lib/mosquitto-sistomate
sudo install -o root -g root -m 644 systemd/mosquitto-sistomate.service /etc/systemd/system/
sudo systemctl daemon-reload
sudo systemctl enable --now mosquitto-sistomate
sudo firewall-cmd --add-port=1884/tcp --add-port=9002/tcp --permanent && sudo firewall-cmd --reload
```

## Uji

```bash
bash tools/test-mqtt-broker.sh    # 29 assertion: auth, ACL per-device, WS read-only, PingPong aman
bash tools/test-mqtt-local.sh     # 23 assertion: 6 skenario wajib Task 2 (marker unik, isolasi)
```

Bukti terakhir: `tools/backup-mqtt-20261119/task1-test.log` (broker) dan
`task2-test.log` / `task2-test-run2.log` (subscriber, 23/23 GREEN 2x).

Catatan uji broker: broker meng-ACK publish lalu **drop senyap** jika ACL menolak, jadi
bukti "ditolak" diukur lewat **non-delivery** (subscriber tidak menerima marker),
bukan kode balik `mosquitto_pub`.

Catatan uji subscriber: seluruh baris uji diidentifikasi lewat **penanda unik**
(sensor `rgb_red` 990000-999999, aktuator `actuator_name=POMPA-UJI-MQTT`), bukan
COUNT total, sehingga tahan terhadap penulis lain (ESP32 HTTP ke produksi, simulator
pratintjau). Skenario 6 memakai 3 bukti keras: env unit = `sistomate_webtest`,
nol jejak penanda uji di produksi, dan delta produksi == delta POST `/ingest.php`
(semua tulisan produksi terjelaskan oleh ESP32 HTTP, bukan jalur MQTT).

## Cek kesehatan cepat

```bash
systemctl status mosquitto mosquitto-sistomate sistomate-mqtt-sub sistomate-config-pub
ss -ltn | grep -E ':(1883|1884|9002)'            # 1883 PingPong, 1884+9002 SISTOMATE
journalctl -u mosquitto-sistomate -f
journalctl -u mosquitto --since today | grep -i warning   # harus kosong
```

## Rollback (≤5 menit)

```bash
sudo systemctl disable --now mosquitto-sistomate   # PingPong TIDAK disentuh
sudo firewall-cmd --remove-port=1884/tcp --remove-port=9002/tcp --permanent && sudo firewall-cmd --reload
sudo rm -f /etc/systemd/system/mosquitto-sistomate.service && sudo systemctl daemon-reload
# hapus juga /etc/mosquitto/sistomate.* dan /var/lib/mosquitto-sistomate bila perlu
```

Jalur HTTP (`ingest.php`) tidak pernah diubah di tiket ini. Firmware tetap
berfungsi penuh tanpa broker.

## Catatan migrasi mosquitto 3.0

`per_listener_settings` sudah terdeprecate di 2.1.2 (dipakai karena
`password_file`/`acl_file` belum punya varian `listener_*`). Saat upgrade
mosquitto 3.0, cek apakah opsi per-listener baru sudah tersedia.
