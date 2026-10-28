#!/usr/bin/env node
/**
 * whisper.js — Tool komunikasi mandiri SISTOMATE antar sesi OpenCode.
 * Dipakai S2 (implementer) buat ngirim pesan ke S1 (tech lead), dll.
 *
 * Pakai: node whisper.js <s1|s2|session-id> "pesan"
 * Contoh: node whisper.js s1 "Halo S1! ..."
 */

const fs = require('fs');
const os = require('os');
const path = require('path');

const SERVICE_FILE = path.join(os.homedir(), '.local/state/opencode/service.json');

// Alias sesi tim SISTOMATE
const SESSIONS = {
  s1: 'ses_e88385ca7ffeSi21zAc48yzIoT', // sistomate-1 (Tech Lead & Auditor)
  s2: 'ses_e885e6770ffeEYAewzV06IFdNP', // sistomate-2 (Implementer & Coder)
};

function fail(msg) {
  console.error('GAGAL:', msg);
  process.exit(1);
}

async function main() {
  const [, , targetArg, ...messageParts] = process.argv;
  if (!targetArg || messageParts.length === 0) {
    fail(`Pakai: node whisper.js <s1|s2|session-id> "pesan"`);
  }

  const message = messageParts.join(' ');
  const targetId = SESSIONS[targetArg.toLowerCase()] || targetArg;
  if (!targetId.startsWith('ses_')) {
    fail(`Target tidak dikenal: ${targetArg} (pakai alias s1/s2 atau ID sesi ses_...)`);
  }

  // Credential OpenCode service
  let svc;
  try {
    svc = JSON.parse(fs.readFileSync(SERVICE_FILE, 'utf8'));
  } catch (e) {
    fail(`Gagal baca ${SERVICE_FILE}: ${e.message}`);
  }
  const url = svc.url || (svc.service && svc.service.url);
  const password = svc.password || (svc.service && svc.service.password);
  if (!url || !password) {
    fail('svc.url / svc.password tidak ada di service.json');
  }

  const auth = 'Basic ' + Buffer.from('opencode:' + password).toString('base64');
  const endpoint = `${url}/api/session/${targetId}/prompt`;

  try {
    const res = await fetch(endpoint, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': auth,
      },
      body: JSON.stringify({ text: message }),
    });

    if (!res.ok) {
      const body = await res.text().catch(() => '');
      fail(`HTTP ${res.status} ${res.statusText} — ${body.slice(0, 300)}`);
    }

    const data = await res.json().catch(() => ({}));
    console.log(`Whisper terkirim ke ${targetArg} (${targetId})`);
    console.log(JSON.stringify(data).slice(0, 500));
  } catch (e) {
    fail(`Request gagal: ${e.message}`);
  }
}

main();
