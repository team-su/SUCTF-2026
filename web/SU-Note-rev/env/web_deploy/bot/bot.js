#!/usr/bin/env node
'use strict';

const puppeteer = require('puppeteer-core');
const { URL } = require('url');
const os = require('os');
const path = require('path');
const fs = require('fs/promises');

const BOT_COOKIE_URL = 'http://127.0.0.1:80/';

async function main() {
  const targetRaw = process.argv[2];
  const sessionId = process.argv[3];
  const baseRaw = process.env.BOT_BASE_URL || 'http://127.0.0.1:80';
  const chromePath = process.env.CHROME_BIN || '/usr/bin/google-chrome';
  const waitRaw = process.env.BOT_WAIT_MS || '12000';
  const postWaitMs = Math.max(1000, Math.min(parseInt(waitRaw, 10) || 12000, 300000));

  if (!targetRaw || !sessionId) {
    throw new Error('Usage: node bot.js <target_url> <session_id>');
  }

  const base = new URL(baseRaw);
  const target = new URL(targetRaw, base);

  if (target.protocol !== 'http:' && target.protocol !== 'https:') {
    throw new Error('target must use http/https');
  }

  let browser;
  let profileDir = '';

  profileDir = await fs.mkdtemp(path.join(os.tmpdir(), 'su-note-bot-'));
  browser = await puppeteer.launch({
    executablePath: chromePath,
    headless: true,
    userDataDir: profileDir,
    args: [
      '--no-sandbox',
      '--disable-setuid-sandbox',
      '--disable-dev-shm-usage',
      '--disable-gpu'
    ]
  });

  try {
    const page = await browser.newPage();
    await page.setCookie({
      url: BOT_COOKIE_URL,
      name: 'PHPSESSID',
      value: sessionId,
      path: '/',
      httpOnly: true,
      sameSite: 'Lax'
    });

    await page.goto(target.toString(), {
      waitUntil: 'domcontentloaded',
      timeout: 30000
    });

    await new Promise((resolve) => setTimeout(resolve, postWaitMs));
    console.log('BOT_OK');
  } finally {
    if (browser) {
      await browser.close().catch(() => {});
    }
    if (profileDir) {
      await fs.rm(profileDir, { recursive: true, force: true }).catch(() => {});
    }
  }
}

main().catch((err) => {
  const msg = err && err.message ? err.message : String(err);
  console.error(`BOT_ERROR: ${msg}`);
  process.exit(1);
});
