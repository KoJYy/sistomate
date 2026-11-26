// ============================================================================
//  KONFIGURASI — EDIT HANYA BAGIAN INI
// ============================================================================
//  Cara pakai:
//    1. Nilai WiFi/server/MQTT ada langsung di bagian "1" di bawah.
//    2. Klik Upload di Arduino IDE.
//    3. Jangan commit nilai asli (WiFi/API key/password MQTT) ke GitHub.
// ----------------------------------------------------------------------------

// --- 1. WiFi, server, dan akun MQTT ---
// Isi nilai asli hanya di salinan lokal. Jangan commit kredensial.
const char* WIFI_SSID  = "YOUR_WIFI_SSID";
const char* WIFI_PASS  = "YOUR_WIFI_PASSWORD";
const char* SERVER_URL = "http://192.168.1.100/ingest.php";
const char* API_KEY    = "sm_YOUR_SECRET_API_KEY";
static const char* MQTT_HOST = "YOUR_MQTT_HOST";
static const char* MQTT_PASS = "YOUR_MQTT_PASSWORD";

const int DEVICE_ID = 1;  // id_device di tabel devices

// --- 2. MQTT (jalur utama, port 1884) ---
#define USE_MQTT 1          // 1 = pakai MQTT, 0 = HTTP saja
#define USE_HTTP 1          // 1 = HTTP dipakai sebagai cadangan
static const int   MQTT_PORT      = 1884;
static const char* MQTT_USER      = "esp32-1";
static const char* MQTT_CLIENT_ID = "sistomate-esp32-1";

// ============================================================================
//  SELESAI KONFIGURASI — bagian di bawah tidak perlu diubah
// ============================================================================

// SISTOMATE — purwarupa smart greenhouse
// ESP32 + soil moisture + DHT11 + TCS34725 + relay 2×2-ch (pompa air, pompa pupuk, kipas)
// Data sensor & aktuator: publish MQTT QoS1 (jalur utama) atau HTTP POST ke
// ingest.php (fallback) -> MySQL sistomate. Satu jalur per siklus 2 detik.
//
// Pinout (hindari strapping pin 0/2/12/15, dan GPIO14 yang memancarkan PWM saat boot):
//   Soil moisture (analog) : GPIO 34
//   DHT11 (digital)        : GPIO 13
//   TCS34725 I2C SDA/SCL   : GPIO 21 / GPIO 22
//   Relay A CH1 Pompa Air  : GPIO 26  (active-LOW)
//   Relay A CH2 Pompa Pupuk: GPIO 25  (active-LOW)  <- pindah dari GPIO14 (PWM saat boot)
//   Relay B CH1 Kipas      : GPIO 27  (active-LOW)
//   Relay B CH2 cadangan   : (belum dipakai; GPIO14 dibebaskan)
//
// Logika (nilai default jurnal; otomatis ikut configs web lewat MQTT retained
// atau respons ingest.php):
//   soil < 40%      -> pompa air ON ; soil >= 60% -> OFF  (histeresis)
//   suhu > 27 C     -> kipas ON ; suhu <= 25 C -> kipas OFF (histeresis)
//   jadwal 1x/minggu jam 07:00 (NTP) + soil >= soilOnPct -> pompa pupuk ON 7 detik
//     (Sari 2024/JITET: jadwal RTC mingguan, durasi default 7 detik; interlock tanah gak kering)
//   Tiap transisi relay -> catat actuator_logs (MQTT QoS1, fallback HTTP POST)
//   Tiap 2 detik        -> catat sensor_logs; config dari MQTT retained atau respons HTTP
//
// Kalibrasi suhu: data DHT11 dikirim MENTAH (TEMP_OFFSET_C 0.0f). Kalau sensor
// terbukti bias terhadap termometer acuan, isi offset HASIL UKUR (bukan -7C).

#include <WiFi.h>
#include <HTTPClient.h>
#include <Wire.h>
#include <DHT.h>
#include <Adafruit_TCS34725.h>
#include <Preferences.h> // persist counter dosis pupuk anti dobel pas reboot
#include <math.h>
#include <stdlib.h>

// ====== Protokol ganda (TIKET-MQTT-01) ======
// Tiap siklus 2 detik memakai SATU jalur:
//   USE_MQTT=1 & broker terhubung -> publish QoS1 sensor/aktuator/status;
//                                    config datang dari topik config retained.
//   broker putus / USE_MQTT=0     -> HTTP POST seperti sediakala (USE_HTTP),
//                                    config dari respons ingest.php tetap dipakai.
// Kombinasi 0/0 tidak berguna; 1/0 = MQTT tanpa fallback; 0/1 = paksa HTTP.
// (Nilai USE_MQTT dan USE_HTTP diatur di bagian KONFIGURASI paling atas.)

#if USE_MQTT
#include <mqtt_client.h>
#endif

#define PIN_SOIL        34
#define PIN_DHT         13
#define PIN_RELAY_POMPA 26
#define RELAY_PUPUK     25   // GPIO14 memancarkan PWM saat boot -> relay chatter (gate: tools/test-firmware-pins.sh)
#define PIN_RELAY_KIPAS 27
#define RELAY_ON        LOW   // modul relay 2-ch umumnya aktif-LOW
#define RELAY_OFF       HIGH

#define DEFAULT_SOIL_ON_PCT     40.0f  // pompa nyala di bawah ini (%)
#define DEFAULT_SOIL_OFF_PCT    60.0f  // pompa mati di atas ini (%)
#define DEFAULT_TEMP_ON_C       27.0f  // kipas nyala di atas ini (C)
#define DEFAULT_TEMP_OFF_C      25.0f  // kipas mati di bawah ini (C)
#define DEFAULT_FERT_DURATION_S 7      // detik dosis per semprot
// Offset suhu DHT11 (C): keputusan Owner 23 Nov — data yang dikirim ke DB
// memakai nilai terkoreksi -7C (raw 30-32C -> 23-25C, ambang 25-27C).
// Catatan: kontrol kipas ikut nilai terkoreksi, jadi kipas baru nyala saat
// raw >34C. Set 0.0f kalau mau data mentah.
#define TEMP_OFFSET_C           -7.0f

// Pompa air: model DOSIS, bukan nyala-terus. Pot prototipe kecil, pompa celup
// ~25 ml/dtk -> 8 dtk (~200 ml) sudah meluber, jadi 60 dtk (~1,5 L) jauh
// berbahaya. Tiap trigger = tepat 3 dtk (~75 ml), lalu jeda serap supaya air
// meresap dulu (mencegah banjir + short-cycling yang merusak dinamo).
#define PUMP_DOSE_MS            3000UL     // dosis siram: tepat 3 detik (~75 ml)
#define PUMP_SOAK_MS            20000UL    // jeda serap antar dosis (20 detik)
// Fail-safe probe tercabut: tanah selalu 0% -> dosis 75 ml tiap 20 dtk
// (= 225 ml/menit, tetap bisa membanjiri pot). Setelah 3 dosis beruntun tanpa
// kemajuan, berhenti 2 menit dulu.
#define PUMP_MAX_DOSES          3
#define PUMP_LONG_SOAK_MS       120000UL   // jeda panjang 2 menit

// Validasi sensor: probe lepas bikin ADC mentok 0 -> map() jadi 100% "basah"
// palsu; DHT11 lepas balik NaN. Tanpa cek ini firmware melaporkan "sehat".
#define SOIL_RAW_MIN            200        // raw di bawah ini = pin floating/probe lepas (air penuh ~1500)
// Pin ADC yang tidak terhubung ke sensor menghasilkan lonjakan liar (spread > 1500
// antar 11 sampel). Sensor FC-28 sehat spreadnya puluhan, jadi 500 aman sebagai batas.
#define SOIL_SAMPLES            11
#define SOIL_SAMPLE_GAP_MS      5
#define SOIL_MAX_SPREAD         500
// TCS34725 dideteksi sekali saat boot; kalau gagal (brownout/kabel sesaat)
// sensor tidak akan pernah dicoba lagi sampai reboot. Coba ulang tiap 10 dtk.
#define TCS_RETRY_MS            10000UL

// selesai init dengan nilai default jurnal; diperbarui otomatis dari configs web
float soilOnPct     = DEFAULT_SOIL_ON_PCT;
float soilOffPct    = DEFAULT_SOIL_OFF_PCT;
float tempOnC       = DEFAULT_TEMP_ON_C;
float tempOffC      = DEFAULT_TEMP_OFF_C;
int   fertDurationS = DEFAULT_FERT_DURATION_S;

// fertigasi (Sari 2024: 1x/minggu, durasi default 7 detik; interlock Bab 5)
#define FERT_HOUR        7     // jam lokal WIB (NTP GMT+7)
#define FERT_MIN         0
#define FERT_WDAY        1     // hari jadwal: 1 = Senin (0=Minggu .. 6=Sabtu) — 1x/minggu

#define SENSOR_INTERVAL_MS 2000UL  // kirim sensor_logs tiap 2 detik
#define READ_INTERVAL_MS    2000UL  // baca sensor tiap 2 detik (DHT11 minimal 1 dtk)

DHT dht(PIN_DHT, DHT11);
Adafruit_TCS34725 tcs = Adafruit_TCS34725(TCS34725_INTEGRATIONTIME_101MS, TCS34725_GAIN_16X); // naik dari 50ms/4X (sinyal terlalu kecil 21 Okt)

bool pumpOn = false;
bool fanOn  = false;
bool tcsOk  = false;
bool fertOn = false;
unsigned long fertStartMs = 0;
unsigned long pumpStartMs = 0;        // mulai nyala (dasar hitung durasi dosis)
unsigned long pumpSoakUntil = 0;      // dosis berikutnya baru boleh setelah waktu ini
int pumpDoseCount = 0;                // dosis beruntun sejak tanah terakhir membaik
bool soilOk = false;                  // ADC tanah masuk rentang fisik (bukan 0/floating)
bool dhtOk = false;                   // DHT11 menjawab (bukan NaN)
Preferences prefs;
int lastFertKey = 0; // (tahun*1000 + yday) dosis terakhir — cegah dobel dalam sehari

unsigned long lastRead  = 0;
unsigned long lastTcsTry = 0; // percobaan begin() TCS terakhir (retry berkala)
unsigned long lastPost  = 0;

// pembacaan terakhir
float soilPct = 0, airTemp = 0, airHumi = 0;
long soilRaw = 0; // nilai mentah ADC buat kalibrasi
long soilSpread = 0; // selisih max-min 11 sampel: besar = pin floating/kabel lepas
uint16_t r = 0, g = 0, b = 0;

#if USE_MQTT
static void mqttBegin();
#endif
void reportInitialActuatorStates(); // state awal saat boot (definisi setelah markActuator)

void setup() {
  Serial.begin(115200);

  // amankan relay SEBELUM pinMode (ceklek saat boot dihindari)
  digitalWrite(PIN_RELAY_POMPA, RELAY_OFF);
  digitalWrite(RELAY_PUPUK, RELAY_OFF);
  digitalWrite(PIN_RELAY_KIPAS, RELAY_OFF);
  pinMode(PIN_RELAY_POMPA, OUTPUT);
  pinMode(RELAY_PUPUK, OUTPUT);
  pinMode(PIN_RELAY_KIPAS, OUTPUT);

  prefs.begin("sistomate", false);
  lastFertKey = prefs.getInt("fertkey", 0);

  pinMode(PIN_SOIL, INPUT);
  dht.begin();
  Wire.begin(21, 22);
  tcsOk = tcs.begin();
  if (!tcsOk) Serial.println("[!] TCS34725 tidak terdeteksi — RGB pakai 0");

  WiFi.mode(WIFI_STA);
  WiFi.setSleep(false);    // WAJIB: modem sleep bikin auth race gagal terus (root cause 21 Okt)
  WiFi.persistent(false);  // jangan pakai config NVS lama
  WiFi.begin(WIFI_SSID, WIFI_PASS);
  Serial.print("WiFi connect");
  unsigned long t0 = millis();
  while (WiFi.status() != WL_CONNECTED && millis() - t0 < 20000) {
    delay(500);
    Serial.print(".");
  }
  Serial.println();
  if (WiFi.status() == WL_CONNECTED) {
    Serial.print("OK IP=");
    Serial.println(WiFi.localIP());
  } else {
    Serial.println("GAGAL — retry di loop");
  }

  configTime(7 * 3600, 0, "pool.ntp.org", "time.nist.gov"); // NTP WIB — SNTP nyala sendiri begitu WiFi connected

#if USE_MQTT
  // Auto-reconnect esp-mqtt menangani WiFi/broker yang menyusul menyala.
  mqttBegin();
#endif

  // Laporkan state awal 3 aktuator SEKALI saat boot. Tanpa ini DB nyangkut di
  // event terakhir sebelum reboot (mis. "Kipas ON" kemarin padahal sekarang OFF).
  reportInitialActuatorStates();
}

bool postJson(const String& body, String* resp = nullptr) {
  if (WiFi.status() != WL_CONNECTED) return false;
  HTTPClient http;
  http.begin(SERVER_URL);
  http.addHeader("Content-Type", "application/json");
  http.addHeader("X-API-Key", API_KEY);
  http.setTimeout(4000);
  int code = http.POST(body);
  if (resp && code >= 200 && code < 300) *resp = http.getString();
  http.end();
  Serial.printf("POST %d\n", code);
  return code >= 200 && code < 300;
}

bool extractJsonFloatFrom(const String& json, int fromIdx, const char* key, float& outVal) {
  String pat = String("\"") + key + "\"";
  int keyIdx = json.indexOf(pat, fromIdx);
  if (keyIdx < 0) return false;
  int colon = json.indexOf(':', keyIdx + pat.length());
  if (colon < 0) return false;
  int pos = colon + 1;
  int len = json.length();
  while (pos < len && (json[pos] == ' ' || json[pos] == '\t' || json[pos] == '\r' || json[pos] == '\n')) {
    pos++;
  }
  if (pos >= len) return false;
  const char* startPtr = json.c_str() + pos;
  char* endPtr = nullptr;
  float parsed = strtof(startPtr, &endPtr);
  if (endPtr == startPtr || isnan(parsed) || isinf(parsed)) return false;
  outVal = parsed;
  return true;
}

// Menerima dua bentuk payload:
//   - MQTT retained publisher : {"soil_on":40,...}            (flat)
//   - respons ingest.php      : {"status":"ok","config":{...}} (terbungkus)
void syncConfigFromJson(const String& json) {
  int cfgIdx = json.indexOf("\"config\"");
  int from = cfgIdx >= 0 ? cfgIdx : 0;

  float sOn = 0, sOff = 0, tOn = 0, tOff = 0, fDur = 0;
  if (!extractJsonFloatFrom(json, from, "soil_on", sOn) ||
      !extractJsonFloatFrom(json, from, "soil_off", sOff) ||
      !extractJsonFloatFrom(json, from, "temp_on", tOn) ||
      !extractJsonFloatFrom(json, from, "temp_off", tOff) ||
      !extractJsonFloatFrom(json, from, "fert_duration_s", fDur)) {
    return;
  }

  int fertS = (int)(fDur + 0.5f);
  // Validasi sama dengan settings.php agar histeresis selalu aman
  if (sOn < 0.0f || sOn > 100.0f || sOff < 0.0f || sOff > 100.0f || sOn >= sOff) return;
  if (tOn < 10.0f || tOn > 50.0f || tOff < 10.0f || tOff > 50.0f || tOff >= tOn) return;
  if (fertS < 1 || fertS > 60) return;

  bool changed = fabsf(sOn - soilOnPct) > 0.05f ||
                 fabsf(sOff - soilOffPct) > 0.05f ||
                 fabsf(tOn - tempOnC) > 0.05f ||
                 fabsf(tOff - tempOffC) > 0.05f ||
                 fertS != fertDurationS;

  soilOnPct     = sOn;
  soilOffPct    = sOff;
  tempOnC       = tOn;
  tempOffC      = tOff;
  fertDurationS = fertS;

  if (changed) {
    Serial.printf("CONFIG SYNC soil=%.1f..%.1f%% temp=%.1f..%.1fC fert=%ds\n",
                  soilOnPct, soilOffPct, tempOffC, tempOnC, fertDurationS);
  }
}

// ====== Jalur MQTT (esp_mqtt_client) ======
#if USE_MQTT
static esp_mqtt_client_handle_t mqttClient = nullptr;
static volatile bool mqttUp = false;       // true selama sesi MQTT hidup (event task)
static uint32_t mqttBootId = 0;            // acak per boot -> msg_id unik lintas reboot
static uint32_t mqttSeq = 0;
static char topicSensor[40], topicActuator[40], topicStatus[40], topicConfig[40];

// Isi LWT (last will): pesan yang dipublikasikan broker kalau board mati paksa.
static const char* MQTT_STATUS_OFFLINE = "{\"status\":\"offline\"}";

// msg_id unik per pesan (buat dedup subscriber): device-bootid-urut
static String nextMsgId() {
  mqttSeq++;
  char buf[48];
  snprintf(buf, sizeof(buf), "%u-%08lx-%lu",
           (unsigned)DEVICE_ID, (unsigned long)mqttBootId, (unsigned long)mqttSeq);
  return String(buf);
}

// Siap kirim: sesi MQTT hidup DAN WiFi tersambung (putus WiFi = langsung fallback).
static bool mqttReady() {
  return mqttClient != nullptr && mqttUp && WiFi.status() == WL_CONNECTED;
}

// Publish QoS1; true = diterima esp-mqtt (diteruskan ke broker).
static bool mqttPublish(const char* topic, const String& payload, bool retain = false) {
  if (!mqttReady()) return false;
  int mid = esp_mqtt_client_publish(mqttClient, topic, payload.c_str(),
                                    payload.length(), 1, retain ? 1 : 0);
  if (mid < 0) {
    Serial.println("MQTT publish gagal -> pakai jalur HTTP");
    return false;
  }
  return true;
}

// Event broker: connect -> status retained + subscribe config retained;
// putus -> mqttUp=false supaya siklus berikutnya jatuh ke HTTP (<= 2 detik).
static void mqttEvent(void* arg, esp_event_base_t base, int32_t id, void* data) {
  (void)arg;
  (void)base;
  esp_mqtt_event_handle_t e = (esp_mqtt_event_handle_t)data;
  switch ((esp_mqtt_event_id_t)id) {
    case MQTT_EVENT_CONNECTED:
      mqttUp = true;
      esp_mqtt_client_publish(mqttClient, topicStatus, "{\"status\":\"online\"}",
                              0, 1, 1); // pasangan LWT offline
      esp_mqtt_client_subscribe(mqttClient, topicConfig, 1); // retained langsung dikirim broker
      Serial.println("MQTT terhubung: status=online retained + subscribe config retained");
      break;
    case MQTT_EVENT_DISCONNECTED:
      if (mqttUp) Serial.println("MQTT putus -> jalur HTTP fallback");
      mqttUp = false;
      break;
    case MQTT_EVENT_DATA:
      if (e->topic_len == (int)strlen(topicConfig) &&
          strncmp(e->topic, topicConfig, e->topic_len) == 0) {
        if (e->current_data_offset != 0 || e->total_data_len > e->data_len) {
          Serial.println("config MQTT terpotong -> diabaikan");
          break;
        }
        syncConfigFromJson(String(e->data, e->data_len));
      }
      break;
    default:
      break;
  }
}

static void mqttBegin() {
  snprintf(topicSensor,   sizeof(topicSensor),   "sistomate/%d/sensor",   DEVICE_ID);
  snprintf(topicActuator, sizeof(topicActuator), "sistomate/%d/actuator", DEVICE_ID);
  snprintf(topicStatus,   sizeof(topicStatus),   "sistomate/%d/status",   DEVICE_ID);
  snprintf(topicConfig,   sizeof(topicConfig),   "sistomate/%d/config",   DEVICE_ID);
  mqttBootId = esp_random();

  esp_mqtt_client_config_t cfg = {};
  cfg.host              = MQTT_HOST;
  cfg.port              = MQTT_PORT;
  cfg.client_id         = MQTT_CLIENT_ID;
  cfg.username          = MQTT_USER;
  cfg.password          = MQTT_PASS;
  cfg.keepalive         = 15; // deteksi putus cepat (uji: stop broker -> <= 2 dtk HTTP)
  cfg.disable_auto_reconnect = false; // auto reconnect + backoff internal esp-mqtt
  cfg.reconnect_timeout_ms   = 3000;
  cfg.lwt_topic         = topicStatus;
  cfg.lwt_msg           = MQTT_STATUS_OFFLINE; // broker publish saat koneksi mati paksa
  cfg.lwt_qos           = 1;
  cfg.lwt_retain        = 1;

  mqttClient = esp_mqtt_client_init(&cfg);
  if (!mqttClient) {
    Serial.println("MQTT init gagal -> jalur HTTP saja");
    return;
  }
  esp_mqtt_client_register_event(mqttClient, MQTT_EVENT_ANY, mqttEvent, NULL);
  esp_mqtt_client_start(mqttClient);
  Serial.printf("MQTT start: %s:%d user=%s client_id=%s (auto-reconnect ON)\n",
                MQTT_HOST, MQTT_PORT, MQTT_USER, MQTT_CLIENT_ID);
}
#endif // USE_MQTT

// event aktuator ditunda (per-slot) sampai terkirim sukses (MQTT/HTTP), retry di loop(),
// biar row actuator_logs gak hilang kalau WiFi/network lagi mati.
// 3 slot terpisah: satu event gak nimpem event lain kalo kirim lagi pending
#define ACT_POMPA 0
#define ACT_KIPAS 1
#define ACT_PUPUK 2
#define ACT_COUNT 3
bool actDirty[ACT_COUNT] = {false, false, false};
bool actOn[ACT_COUNT] = {false, false, false};
const char* actName[ACT_COUNT] = {"", "", ""};

void markActuator(int slot, const char* name, bool on) {
  actName[slot] = name;
  actOn[slot] = on;
  actDirty[slot] = true;
}

void flushActuator() {
  for (int i = 0; i < ACT_COUNT; i++) {
    if (!actDirty[i]) continue;
    String body = String("{\"type\":\"actuator\",\"device_id\":") + DEVICE_ID +
                  ",\"actuator_name\":\"" + actName[i] +
                  "\",\"status\":\"" + (actOn[i] ? "ON" : "OFF") + "\"}";
    bool sent = false;
#if USE_MQTT
    // MQTT terhubung -> publish saja (tanpa duplikat ke HTTP)
    if (mqttReady()) {
      String mbody = String("{\"msg_id\":\"") + nextMsgId() + "\"," + body.substring(1);
      sent = mqttPublish(topicActuator, mbody);
    }
#endif
#if USE_HTTP
    if (!sent) sent = postJson(body); // broker putus -> mekanisme lama (retry tiap loop)
#endif
    if (sent) actDirty[i] = false; // sukses saja di-clear, gagal retry nanti
  }
}

void setPump(bool on) {
  if (on == pumpOn) return;
  pumpOn = on;
  if (on) pumpStartMs = millis();
  digitalWrite(PIN_RELAY_POMPA, on ? RELAY_ON : RELAY_OFF);
  Serial.printf("POMPA %s\n", on ? "ON" : "OFF");
  markActuator(ACT_POMPA, "Pompa Air", on);
}

void setFert(bool on) {
  if (on == fertOn) return;
  fertOn = on;
  digitalWrite(RELAY_PUPUK, on ? RELAY_ON : RELAY_OFF);
  if (on) fertStartMs = millis();
  Serial.printf("PUPUK %s\n", on ? "ON" : "OFF");
  markActuator(ACT_PUPUK, "Pompa Pupuk", on);
}

void setFan(bool on) {
  if (on == fanOn) return;
  fanOn = on;
  digitalWrite(PIN_RELAY_KIPAS, on ? RELAY_ON : RELAY_OFF);
  Serial.printf("KIPAS %s\n", on ? "ON" : "OFF");
  markActuator(ACT_KIPAS, "Kipas Exhaust", on);
}

// State awal saat boot: relay sudah OFF (lihat setup), jadi laporkan OFF untuk
// ketiganya supaya dashboard tidak menampilkan status basi dari sesi sebelumnya.
void reportInitialActuatorStates() {
  markActuator(ACT_POMPA, "Pompa Air", pumpOn);
  markActuator(ACT_KIPAS, "Kipas Exhaust", fanOn);
  markActuator(ACT_PUPUK, "Pompa Pupuk", fertOn);
}

// fertigasi: dosis terkonfigurasi (default 7 detik) tiap minggu jam 07:00 (Sari 2024), batal kalo tanah kering
void fertigasi() {
  if (!soilOk) return; // probe lepas -> jangan dosis (interlock tidak bisa dipercaya)
  if (fertOn) {
    if (millis() - fertStartMs >= (unsigned long)fertDurationS * 1000UL) setFert(false);
    else if (soilPct < soilOnPct) setFert(false); // interlock juga di tengah dosis
    return;
  }
  struct tm t;
  if (!getLocalTime(&t, 0)) return; // NTP belum sinkron (tahun < 2016 = false)
  if (t.tm_hour != FERT_HOUR || t.tm_min != FERT_MIN) return;
  if (t.tm_wday != FERT_WDAY) return;           // hanya hari Senin -> jadwal 1x/minggu (bukan tiap hari)
  int key = (t.tm_year + 1900) * 1000 + t.tm_yday;
  if (key == lastFertKey) return;           // hari ini udah dapat dosis
  if (soilPct < soilOnPct) return;          // tanah kering -> lewati minggu ini (retry selama menit jadwal)
  lastFertKey = key;
  prefs.putInt("fertkey", key);
  setFert(true);
}

// heuristik status tanaman dari kondisi sensor (3 kondisi)
// layu (tanah kering atau suhu panas/fanOn) > sakit (daun tidak hijau) > sehat
const char* plantStatus() {
  if (soilPct < soilOnPct || fanOn) return "layu"; // kurang air (< soilOnPct) atau stres panas (kipas aktif)
  if (tcsOk) {
    // kalibrasi 21 Okt malam (data 4468 baris, bimodal jelas):
    //   hijau asli (ambient): G/R = 0.94–1.10  (R kadang >G, syarat g>r bikin salah sakit)
    //   kuning (senter putih): G/R ≈ 0.52
    //   lembah datanya 0.60–0.90 -> ambang G/R 0.75 = titik tengah, margin lebar dua sisi.
    // lowBlue dibuang: hijau & kuning sama-sama lolos (b/max ≈ 0.48 vs 0.22) — gak diskriminatif.
    bool redDom = (unsigned long)g * 4 < (unsigned long)r * 3; // G/R < 0.75
    if (redDom && r > 100) return "sakit"; // r>100 = sinyal nyata, bukan noise
  }
  return "sehat";
}

// Sampling median 11x @5ms: buang spike noise ADC, sekaligus ukur spread.
// Pin yang tidak terhubung ke sensor menghasilkan spread >1500 (lihat tools adc-diag),
// sensor sehat <100 — jadi spread adalah bukti objektif "kabel lepas".
long readSoilMedian() {
  long v[SOIL_SAMPLES];
  for (int i = 0; i < SOIL_SAMPLES; i++) {
    v[i] = analogRead(PIN_SOIL);
    delay(SOIL_SAMPLE_GAP_MS);
  }
  for (int i = 1; i < SOIL_SAMPLES; i++) { // insertion sort (n kecil)
    long k = v[i];
    int j = i - 1;
    while (j >= 0 && v[j] > k) { v[j + 1] = v[j]; j--; }
    v[j + 1] = k;
  }
  soilSpread = v[SOIL_SAMPLES - 1] - v[0];
  return v[SOIL_SAMPLES / 2];
}

void readSensors() {
  long raw = readSoilMedian();
  soilRaw = raw;
  // raw ~0 atau sampel liar = probe lepas/kabel tidak nyambung. Jangan dipetakan,
  // nanti terbaca 100% basah dan tampil "sehat".
  soilOk = (raw >= SOIL_RAW_MIN) && (soilSpread <= SOIL_MAX_SPREAD);
  if (soilOk) {
    soilPct = constrain(map(raw, 4095, 1500, 0, 100), 0, 100); // kalibrasi 21 Okt: udara=4095, air penuh=1500
  }

  float t = dht.readTemperature();
  float h = dht.readHumidity();
  dhtOk = !isnan(t) && !isnan(h) && h > 0.0f; // NaN/0% = DHT11 tidak menjawab
  if (dhtOk) {
    float adj = t + TEMP_OFFSET_C;
    airTemp = adj < 0.0f ? 0.0f : adj;
    airHumi = h;
  }

  if (tcsOk) {
    uint16_t clear = 0;
    tcs.getRawData(&r, &g, &b, &clear);
  }
}

void loop() {
  unsigned long now = millis();

  // reconnect WiFi kalau putus
  if (WiFi.status() != WL_CONNECTED) {
    static unsigned long lastRetry = 0;
    if (now - lastRetry > 5000) {
      lastRetry = now;
      WiFi.disconnect();
      WiFi.begin(WIFI_SSID, WIFI_PASS);
      Serial.println("WiFi retry");
    }
  }

  // TCS dideteksi lewat I2C; kalau gagal sekali saat boot (brownout/kabel),
  // coba ulang supaya tidak terkunci "lepas" sampai reboot.
  if (!tcsOk && now - lastTcsTry >= TCS_RETRY_MS) {
    lastTcsTry = now;
    tcsOk = tcs.begin();
    if (tcsOk) Serial.println("TCS34725 terdeteksi (retry)");
  }

  // Dosis pompa air dipantau TIAP loop (delay 50 ms), bukan di dalam blok
  // READ_INTERVAL_MS 2 dtk: kalau dipantau per 2 dtk, dosis 3 dtk bisa jadi
  // 4-5 dtk (~125 ml) dan pot mini kebanjiran.
  if (pumpOn && now - pumpStartMs >= PUMP_DOSE_MS) {
    setPump(false);
    pumpSoakUntil = now + PUMP_SOAK_MS;
    Serial.printf("POMPA OFF (dosis %lu detik selesai, jeda serap %lu detik)\n",
                  PUMP_DOSE_MS / 1000UL, PUMP_SOAK_MS / 1000UL);
  }

  if (now - lastRead >= READ_INTERVAL_MS) {
    lastRead = now;
    readSensors();

    // Model dosis: pompa TIDAK ditahan nyala oleh histeresis. Tiap trigger
    // memberi tepat PUMP_DOSE_MS, lalu jeda serap PUMP_SOAK_MS.
    bool pumpSoak = (long)(now - pumpSoakUntil) < 0;

    if (!soilOk || !dhtOk) {
      // Sensor lepas/rusak: jangan kirim angka palsu, aktuator ke posisi aman.
      static unsigned long lastWarn = 0;
      if (now - lastWarn >= 5000) {
        lastWarn = now;
        Serial.printf("SENSOR INVALID soil=%s dht=%s tcs=%s (raw=%ld spread=%ld) -> data tidak dikirim, aktuator OFF\n",
                      soilOk ? "ok" : "LEPAS", dhtOk ? "ok" : "LEPAS", tcsOk ? "ok" : "LEPAS",
                      soilRaw, soilSpread);
      }
      setPump(false);
      // Jangan langsung dosis lagi begitu sensor pulih (sensor bisa flapping).
      pumpSoakUntil = now + PUMP_SOAK_MS;
      setFan(false);
    } else {
      // Tanah sudah cukup basah -> tidak perlu dosis; batalkan jeda supaya
      // kalau mengering lagi pompa tidak menunggu sia-sia.
      if (soilPct >= soilOffPct) pumpSoakUntil = 0;
      // Ada kemajuan (tanah naik ke ambang ON) -> jatah dosis beruntun pulih.
      if (soilPct >= soilOnPct) pumpDoseCount = 0;
      pumpSoak = (long)(now - pumpSoakUntil) < 0;

      if (!pumpOn && !pumpSoak && soilPct < soilOnPct) {
        if (pumpDoseCount >= PUMP_MAX_DOSES) {
          // Fail-safe: probe tercabut / tanah tidak merespons dosis -> berhenti.
          pumpSoakUntil = now + PUMP_LONG_SOAK_MS;
          pumpDoseCount = 0; // setelah jeda panjang, jatah pulih
          Serial.printf("POMPA JEDA PANJANG (%lu detik) — %d dosis tanpa kemajuan tanah\n",
                        PUMP_LONG_SOAK_MS / 1000UL, PUMP_MAX_DOSES);
        } else {
          pumpDoseCount++;
          setPump(true);
        }
      }
      setFan(airTemp > tempOnC ? true : (airTemp <= tempOffC ? false : fanOn));
    }
  }

  if ((now - lastPost >= SENSOR_INTERVAL_MS) && soilOk && dhtOk) {
    lastPost = now;
    String body = String("{\"type\":\"sensor\",\"device_id\":") + DEVICE_ID +
                  ",\"air_temperature\":" + String(airTemp, 1) +
                  ",\"air_humidity\":" + String(airHumi, 1) +
                  ",\"soil_moisture\":" + String(soilPct, 1) +
                  ",\"rgb_red\":" + r +
                  ",\"rgb_green\":" + g +
                  ",\"rgb_blue\":" + b +
                  ",\"plant_status\":\"" + plantStatus() + "\"}";

    bool viaMqtt = false;
#if USE_MQTT
    // MQTT terhubung -> publish sensor + status QoS1, TIDAK POST HTTP
    if (mqttReady()) {
      String mbody = String("{\"msg_id\":\"") + nextMsgId() + "\"," + body.substring(1);
      viaMqtt = mqttPublish(topicSensor, mbody);
      if (viaMqtt) {
        mqttPublish(topicStatus,
                    String("{\"status\":\"online\",\"device_id\":") + DEVICE_ID +
                        ",\"msg_id\":\"" + nextMsgId() + "\"}", true);
      }
    }
#endif
#if USE_HTTP
    if (!viaMqtt) {
      // MQTT putus -> POST seperti sediakala; config dari respons tetap dipakai
      String resp;
      if (postJson(body, &resp)) syncConfigFromJson(resp);
    }
#endif
    Serial.printf("SENSOR suhu=%.1fC rh=%.0f%% soil=%.0f%% raw=%ld rgb=%u,%u,%u %s\n",
                  airTemp, airHumi, soilPct, soilRaw, r, g, b, plantStatus());

    // RGB opsional: kalau TCS lepas, data tetap dikirim (rgb=0) tapi beri tahu
    // di serial supaya tidak terlihat seperti "sensor normal" begitu saja.
    if (!tcsOk) {
      static unsigned long lastRgbWarn = 0;
      if (now - lastRgbWarn >= 30000) {
        lastRgbWarn = now;
        Serial.println("RGB LEPAS (TCS34725 tidak terdeteksi) — data tetap dikirim dengan rgb=0");
      }
    }
  }

  flushActuator(); // kirim/retry event aktuator yang belum sukses ter-post
  fertigasi();     // cek jadwal pupuk tiap loop (presisi durasi 50ms, bukan 2 dtk)

  delay(50);
}
