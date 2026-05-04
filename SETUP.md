# Setup Guide — Business Audit System

## Prerequisites

| Tool | Version |
|------|---------|
| PHP | ^8.2 |
| Composer | ^2.x |
| MySQL | ^8.0 |
| Redis | ^7.x |
| Node.js | ^20.x (for Puppeteer microservice, Step 4) |

---

## 1. Create the Laravel project (run once)

```bash
composer create-project laravel/laravel . "^11.0"
```

> Run this inside the project root. It scaffolds the full Laravel skeleton.
> After it completes, copy the files in this repo on top.

---

## 2. Install project packages

```bash
composer require \
  barryvdh/laravel-dompdf:^2.2 \
  guzzlehttp/guzzle:^7.8 \
  predis/predis:^2.2 \
  resend/resend-laravel:^0.10
```

---

## 3. Environment setup

```bash
cp .env.example .env
php artisan key:generate
```

Fill in these values in `.env`:

- `DB_*` — MySQL credentials
- `ANTHROPIC_API_KEY` — from console.anthropic.com
- `GOOGLE_PLACES_API_KEY` — from Google Cloud Console
- `RESEND_API_KEY` — from resend.com (or use SMTP for local dev)
- `REDIS_*` — Redis connection

---

## 4. Database

```bash
php artisan migrate
```

This creates: `businesses`, `reports`, `email_logs`, `job_logs`.

---

## 5. Queue worker

```bash
# Development (foreground)
php artisan queue:work redis --queue=scraping,ai,email,default

# Production (use Supervisor)
# See config/supervisor.conf (added in Step 9)
```

---

## 6. Scheduler (for weekly email limit)

Add this cron entry on your server:

```cron
* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
```

---

## 7. Tailwind CSS

```bash
npm install
npm run dev      # development
npm run build    # production
```

---

## Directory Structure (after full build)

```
app/
  Http/
    Controllers/
      Admin/
        DashboardController.php
        BusinessController.php
        ReportController.php
        EmailLogController.php
    Requests/
      StoreCampaignRequest.php
  Jobs/
    FetchBusinessesJob.php
    ScrapeBusinessJob.php
    GenerateReportJob.php
    SendEmailJob.php
  Mail/
    AuditOutreachMail.php
  Models/
    Business.php
    Report.php
    EmailLog.php
    JobLog.php
  Services/
    BusinessFetcherService.php
    ScraperService.php
    AIService.php
    ReportService.php
    EmailService.php
config/
  audit.php
database/
  migrations/
    ..._create_businesses_table.php
    ..._create_reports_table.php
    ..._create_email_logs_table.php
    ..._create_job_logs_table.php
resources/
  views/
    admin/
      dashboard.blade.php
      businesses/index.blade.php
      reports/index.blade.php
      email-logs/index.blade.php
    emails/
      outreach.blade.php
    pdf/
      report.blade.php
    layouts/
      app.blade.php
routes/
  web.php
storage/
  app/
    pdfs/reports/
```
