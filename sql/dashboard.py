#!/usr/bin/env python3
"""SISTOMATE dashboard mini (presentasi minggu-5). Jalan: python3 sql/dashboard.py -> buka http://localhost:8000"""
import getpass
from http.server import BaseHTTPRequestHandler, HTTPServer
import pymysql

DB_HOST, DB_USER, DB_NAME = "127.0.0.1", "root", "sistomate"
DB_PW = ""

def q(sql, args=()):
    con = pymysql.connect(host=DB_HOST, user=DB_USER, password=DB_PW, database=DB_NAME)
    try:
        with con.cursor(pymysql.cursors.DictCursor) as c:
            c.execute(sql, args)
            return c.fetchall()
    finally:
        con.close()

PAGE = """<!doctype html><html lang=id><meta charset=utf-8><meta name=viewport content="width=device-width,initial-scale=1">
<title>SISTOMATE Dashboard</title><style>
body{font-family:system-ui,sans-serif;max-width:900px;margin:auto;padding:16px;background:#f4f7f4;color:#222}
h1{color:#1b5e20}h2{color:#2e7d32;border-bottom:2px solid #a5d6a7;padding-bottom:4px}
.card{background:#fff;border-radius:10px;padding:12px 16px;margin:8px 0;box-shadow:0 1px 4px #0002}
.badge{display:inline-block;padding:2px 10px;border-radius:12px;color:#fff;font-weight:bold}
.sehat{background:#43a047}.kering{background:#fb8c00}.layu{background:#e53935}.sakit{background:#8e24aa}
table{border-collapse:collapse;width:100%;background:#fff}td,th{border:1px solid #ccc;padding:6px 8px;text-align:left}
.bar{height:18px;border-radius:4px;background:#66bb6a;margin:2px 0}
canvas{background:#fff;border-radius:10px;width:100%}
</style><h1>🌱 SISTOMATE — Monitoring Greenhouse Tomat</h1>
<h2>Bacaan Terbaru per Perangkat</h2>@@CARDS@@
<h2>Distribusi Status Tanaman</h2>@@DIST@@
<h2>Grafik Kelembapan Tanah (ESP32-Greenhouse-1)</h2><canvas id=ch width=800 height=220></canvas>
<script>var D=@@SERIES@@;var c=document.getElementById('ch').getContext('2d');
var v=D.map(r=>r.soil_moisture),mn=Math.min(...v)-5,mx=Math.max(...v)+5;
c.strokeStyle='#2e7d32';c.lineWidth=2;c.beginPath();
D.forEach((r,i)=>{var x=40+i*(720/(D.length-1||1)),y=200-(r.soil_moisture-mn)/(mx-mn)*170;c[i?'lineTo':'moveTo'](x,y);});
c.stroke();c.fillStyle='#222';c.font='12px sans-serif';
D.forEach((r,i)=>{var x=40+i*(720/(D.length-1||1));c.fillText(r.soil_moisture+'%',x-12,218);});</script>
<h2>Riwayat Penyiraman Pompa</h2>@@WATER@@
<h2>Dokumentasi Foto</h2>@@GAL@@
<p><small>Data live dari MySQL <b>sistomate</b> · refresh browser untuk update</small></p>"""

class H(BaseHTTPRequestHandler):
    def do_GET(self):
        cards = "".join(
            f"<div class=card><b>{r['device_name']}</b> · {r['recorded_at']} · "
            f"Suhu {r['air_temperature']}°C · Tanah {r['soil_moisture']}% "
            f"<span class='badge {r['plant_status']}'>{r['plant_status']}</span></div>"
            for r in q("""SELECT d.device_name,s.recorded_at,s.air_temperature,s.soil_moisture,s.plant_status
                          FROM sensor_logs s JOIN devices d ON d.id_device=s.id_device
                          WHERE s.recorded_at=(SELECT MAX(s2.recorded_at) FROM sensor_logs s2
                                               WHERE s2.id_device=s.id_device)"""))
        tot = q("SELECT COUNT(*) n FROM sensor_logs")[0]["n"] or 1
        dist = "".join(
            f"<div>{r['plant_status']} ({r['jumlah']})<div class=bar style='width:{r['jumlah']/tot*100:.0f}%'></div></div>"
            for r in q("SELECT plant_status,COUNT(*) jumlah FROM sensor_logs GROUP BY plant_status"))
        series = q("SELECT recorded_at,soil_moisture FROM sensor_logs WHERE id_device=1 ORDER BY recorded_at")
        import json
        js = json.dumps([{"soil_moisture": float(r["soil_moisture"])} for r in series])
        def table(rows):
            if not rows:
                return "<p><i>(kosong)</i></p>"
            h = "".join(f"<th>{k}</th>" for k in rows[0])
            b = "".join("<tr>" + "".join(f"<td>{v}</td>" for v in r.values()) + "</tr>" for r in rows)
            return f"<table><tr>{h}</tr>{b}</table>"
        water = table(q("""SELECT d.device_name,a.actuator_name,a.status,a.executed_at FROM actuator_logs a
                           JOIN devices d ON d.id_device=a.id_device ORDER BY a.executed_at"""))
        gal = table(q("""SELECT d.device_name,g.image_url,g.caption,g.upload_date,u.name pengunggah FROM gallery g
                         JOIN devices d ON d.id_device=g.id_device LEFT JOIN users u ON u.id_user=g.id_user"""))
        body = (PAGE.replace("@@CARDS@@", cards or "<p><i>(belum ada data — load seed.sql dulu)</i></p>")
                     .replace("@@DIST@@", dist).replace("@@SERIES@@", js)
                     .replace("@@WATER@@", water).replace("@@GAL@@", gal)).encode()
        self.send_response(200)
        self.send_header("Content-Type", "text/html; charset=utf-8")
        self.send_header("Content-Length", str(len(body)))
        self.end_headers()
        self.wfile.write(body)
    def log_message(self, format, *args):  # noqa: A002 - signature bawaan http.server
        pass

if __name__ == "__main__":
    DB_PW = getpass.getpass("Password MySQL root: ")
    q("SELECT 1")  # tes koneksi
    print("Buka: http://localhost:8000")
    HTTPServer(("127.0.0.1", 8000), H).serve_forever()
