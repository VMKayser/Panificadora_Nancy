import fs from 'fs';
import path from 'path';
import { chromium } from 'playwright';

// Smoke-test script: visits pages, captures console messages and screenshots
// Usage: node scripts/smoke-test.mjs [baseUrl]
// Example: node scripts/smoke-test.mjs http://localhost:4173/app

const argv = process.argv.slice(2);
const BASE = argv[0] || process.env.SMOKE_BASE || 'http://localhost:4173/app';
const OUT_DIR = path.resolve(process.cwd(), 'scripts', 'smoke-output');
const pages = [
  { name: 'checkout', path: '/checkout' },
  { name: 'vendedor', path: '/vendedor' },
  { name: 'admin-inventario', path: '/admin/inventario' },
  { name: 'mis-pedidos', path: '/mis-pedidos' }
];

async function ensureDir(dir) {
  await fs.promises.mkdir(dir, { recursive: true });
}

function shortStamp() {
  return new Date().toISOString().replace(/[:.]/g, '-');
}

(async () => {
  await ensureDir(OUT_DIR);
  const report = { base: BASE, startedAt: new Date().toISOString(), results: [] };

  const browser = await chromium.launch({ headless: true });
  try {
    for (const p of pages) {
      const url = new URL(p.path, BASE).toString();
      const pageReport = { name: p.name, url, console: [], errors: [], warnings: [], screenshot: null };
      const page = await browser.newPage();

      page.on('console', msg => {
        try {
          const text = msg.text();
          const type = msg.type();
          pageReport.console.push({ type, text });
          if (type === 'error') pageReport.errors.push(text);
          if (type === 'warning') pageReport.warnings.push(text);
        } catch (e) {
          // ignore
        }
      });

      page.on('pageerror', err => {
        pageReport.errors.push(String(err));
      });

      console.log(`Visiting ${url}`);
      try {
        const resp = await page.goto(url, { waitUntil: 'networkidle', timeout: 30000 });
        // wait a bit for any async UI actions to run
        await page.waitForTimeout(1200);

        const fileName = `${p.name.replace(/[^a-z0-9-_]/gi, '_')}_${shortStamp()}.png`;
        const screenshotPath = path.join(OUT_DIR, fileName);
        await page.screenshot({ path: screenshotPath, fullPage: true });
        pageReport.screenshot = path.relative(process.cwd(), screenshotPath);

        pageReport.status = resp ? resp.status() : null;
        pageReport.ok = resp ? resp.ok() : null;
      } catch (err) {
        pageReport.errors.push(String(err));
      }

      report.results.push(pageReport);
      await page.close();
    }
  } finally {
    await browser.close();
  }

  report.finishedAt = new Date().toISOString();
  const outPath = path.join(OUT_DIR, `smoke-results-${shortStamp()}.json`);
  await fs.promises.writeFile(outPath, JSON.stringify(report, null, 2), 'utf8');
  console.log('Smoke test finished. Report written to', outPath);
  process.exit(0);
})();
