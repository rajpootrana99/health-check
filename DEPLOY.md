# Production Deployment Guide

## Prerequisites

| Requirement | Version |
|-------------|---------|
| PHP | 8.2+ with `dom`, `gd`, `zip`, `redis`, `pdo_mysql` extensions |
| Composer | 2.x |
| MySQL | 8.0+ |
| Redis | 7.x |
| Node.js | 20.x |
| Supervisor | any recent version |

---

## 1. Clone & Install

```bash
git clone <your-repo> /var/www/html
cd /var/www/html

composer install --no-dev --optimize-autoloader
npm install && npm run build
```

---

## 2. Environment

```bash
cp .env.example .env
php artisan key:generate
```

Fill in `.env`:

```env
APP_ENV=production
APP_URL=https://yourdomain.com

DB_HOST=127.0.0.1
DB_DATABASE=business_audit
DB_USERNAME=your_user
DB_PASSWORD=your_password

QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1

MAIL_MAILER=resend
RESEND_API_KEY=re_your_key

ANTHROPIC_API_KEY=sk-ant-your_key
GOOGLE_PLACES_API_KEY=AIza_your_key
PAGESPEED_API_KEY=AIza_your_key

RESEND_WEBHOOK_SECRET=whsec_your_secret
WEEKLY_EMAIL_LIMIT=10
```

---

## 3. Database

```bash
php artisan migrate --force
```

Migrations run in order:
1. `create_campaigns_table`
2. `create_businesses_table`  ← FK → campaigns
3. `create_reports_table`     ← FK → businesses
4. `create_email_logs_table`  ← FK → businesses + reports
5. `create_job_logs_table`
6. `create_failed_jobs_table`

---

## 4. Storage

```bash
php artisan storage:link          # create public symlink
mkdir -p storage/app/pdfs/reports
chmod -R 775 storage bootstrap/cache
```

---

## 5. Cache & Optimise

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

---

## 6. Queue Workers (Supervisor)

```bash
sudo cp supervisor/audit-worker.conf /etc/supervisor/conf.d/
sudo mkdir -p /var/log/audit
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start audit:*
```

Verify:
```bash
sudo supervisorctl status
```

---

## 7. Scheduler (Cron)

```bash
crontab -e
```

Add:
```cron
* * * * * cd /var/www/html && php artisan schedule:run >> /dev/null 2>&1
```

---

## 8. Puppeteer Microservice (Optional)

Only needed for JavaScript-rendered sites. Skip for basic deployments.

```bash
cd scraper-service
cp .env.example .env    # set PORT + SCRAPER_SERVICE_SECRET
npm install
npm start               # or use PM2: pm2 start server.js --name scraper
```

---

## 9. Resend Webhook

1. Log in to [resend.com](https://resend.com) → **Webhooks**
2. Add endpoint: `https://yourdomain.com/webhooks/resend`
3. Select events: `email.opened`, `email.clicked`, `email.bounced`, `email.complained`
4. Copy the signing secret → `RESEND_WEBHOOK_SECRET=whsec_...` in `.env`
5. Run `php artisan config:cache` to reload

---

## 10. Verify Installation

```bash
# 1. Check queue workers are running
sudo supervisorctl status

# 2. Check scheduler is active
php artisan schedule:list

# 3. Check all queues are empty/healthy
php artisan queue:status

# 4. Trigger a test fetch (replace values)
# → Open https://yourdomain.com/admin
# → Click "New Campaign"
# → Enter: Business Type = "Dentists", Location = "London"
# → Click "Fetch Businesses"

# 5. Monitor the pipeline
php artisan queue:status          # terminal snapshot
# or open /admin/queue-monitor    # browser UI
```

---

## Post-Deploy

| Check | Command / URL |
|-------|---------------|
| Queue depths | `php artisan queue:status` |
| Failed jobs | `php artisan queue:failed` |
| Scheduler log | `tail -f storage/logs/scheduler.log` |
| App log | `tail -f storage/logs/laravel.log` |
| Worker logs | `tail -f /var/log/audit/worker-ai.log` |
| Admin panel | `https://yourdomain.com/admin` |

---

## Weekly Operations

The system is fully automated after setup. Every Monday at 09:00 it:

1. Picks up to 10 audited businesses with pending emails
2. Sends personalised outreach with PDF report attached
3. Tracks opens and clicks via webhook

Manual overrides available in the admin panel:
- **Re-scrape** — re-run scraping for a specific business
- **Re-audit** — re-run AI audit
- **Regenerate PDF** — rebuild the PDF
- **Dry-run dispatch** — `php artisan audit:dispatch-weekly-emails --dry-run`
