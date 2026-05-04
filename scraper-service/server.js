'use strict';

const express   = require('express');
const puppeteer = require('puppeteer');

const app  = express();
const PORT = process.env.PORT || 3001;
const SECRET = process.env.SCRAPER_SERVICE_SECRET || '';

app.use(express.json());

// ---------------------------------------------------------------------------
// Auth middleware — validates X-Secret header
// ---------------------------------------------------------------------------
app.use((req, res, next) => {
    if (SECRET && req.headers['x-secret'] !== SECRET) {
        return res.status(401).json({ error: 'Unauthorized' });
    }
    next();
});

// ---------------------------------------------------------------------------
// Health check
// ---------------------------------------------------------------------------
app.get('/health', (_req, res) => {
    res.json({ status: 'ok', ts: new Date().toISOString() });
});

// ---------------------------------------------------------------------------
// POST /scrape  { url: "https://example.com" }
// Returns:      { html: "<html>...</html>", url: "...", elapsed_ms: 123 }
// ---------------------------------------------------------------------------
app.post('/scrape', async (req, res) => {
    const { url } = req.body;

    if (!url || !/^https?:\/\//.test(url)) {
        return res.status(422).json({ error: 'A valid URL is required.' });
    }

    const start = Date.now();
    let browser;

    try {
        browser = await puppeteer.launch({
            headless: 'new',
            args: [
                '--no-sandbox',
                '--disable-setuid-sandbox',
                '--disable-dev-shm-usage',
                '--disable-gpu',
            ],
        });

        const page = await browser.newPage();

        // Realistic viewport + user agent
        await page.setViewport({ width: 1280, height: 800 });
        await page.setUserAgent(
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 ' +
            '(KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
        );

        // Block images/fonts/media to speed up scraping
        await page.setRequestInterception(true);
        page.on('request', (req) => {
            const blocked = ['image', 'media', 'font', 'stylesheet'];
            if (blocked.includes(req.resourceType())) {
                req.abort();
            } else {
                req.continue();
            }
        });

        await page.goto(url, {
            waitUntil: 'networkidle2',
            timeout:   25_000,
        });

        // Wait for body text to be populated (handles lazy-loaded frameworks)
        await page.waitForSelector('body', { timeout: 5_000 }).catch(() => {});

        const html = await page.content();

        res.json({
            html,
            url,
            elapsed_ms: Date.now() - start,
        });

    } catch (err) {
        console.error(`[scraper] Failed to scrape ${url}:`, err.message);
        res.status(500).json({
            error: err.message,
            url,
            elapsed_ms: Date.now() - start,
        });
    } finally {
        if (browser) {
            await browser.close().catch(() => {});
        }
    }
});

// ---------------------------------------------------------------------------
// Start
// ---------------------------------------------------------------------------
app.listen(PORT, () => {
    console.log(`[scraper] Puppeteer microservice listening on port ${PORT}`);
});
