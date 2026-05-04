# End-to-End Pipeline

## Complete Flow

```
┌─────────────────────────────────────────────────────────────────────┐
│  ADMIN INPUT                                                        │
│  POST /admin/campaigns                                              │
│  { business_type: "Dentists", location: "London" }                 │
└───────────────────────────┬─────────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────────────┐
│  CampaignController::store()                                        │
│  → Campaign::create()    [status: pending]                          │
│  → FetchBusinessesJob::dispatch()  → queue:default                 │
└───────────────────────────┬─────────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────────────┐
│  FetchBusinessesJob::handle()                                       │
│  → BusinessFetcherService::fetch()                                  │
│     ├── Google Places Text Search API  (up to 3 pages / 60 results)│
│     ├── Google Places Details API      (per place_id)              │
│     └── Business::create()             [status: fetched]           │
│  → Campaign::markAs('processing')                                   │
│  → ScrapeBusinessJob::dispatch() × N  → queue:scraping             │
└───────────────────────────┬─────────────────────────────────────────┘
                            │  (per business with website)
                            ▼
┌─────────────────────────────────────────────────────────────────────┐
│  ScrapeBusinessJob::handle()                                        │
│  → business.markAs('scraping')                                      │
│  → ScraperService::scrape()                                         │
│     ├── HTTP fetch  (Guzzle)                                        │
│     ├── Puppeteer fallback  (if word_count < 80)                   │
│     ├── DOM parse  (title, meta, h1, h2s, forms, CTAs, socials)    │
│     ├── PageSpeed Insights API  (mobile + desktop)                  │
│     ├── Email extraction  (regex on page source)                    │
│     └── Business::update(scraped_data, social URLs, email)         │
│  → business.markAs('scraped')                                       │
│  → GenerateReportJob::dispatch()  → queue:ai                        │
└───────────────────────────┬─────────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────────────┐
│  GenerateReportJob::handle()                                        │
│  → business.markAs('auditing')                                      │
│  → Report::create()  [status: generating]                           │
│  → AIService::audit()                                               │
│     ├── Build system prompt  (15 health check rubric + JSON schema) │
│     ├── Build user prompt    (all scraped data injected)            │
│     ├── POST /v1/messages  → Claude claude-opus-4-6                 │
│     ├── Fallback: POST /v1/chat/completions → OpenAI gpt-4o         │
│     ├── Parse + validate JSON  (exactly 15 checks required)         │
│     └── Normalise scores  (clamp 0-10, recalculate overall_score)  │
│  → Report::update(audit_data, overall_score, summary)               │
│  → business.markAs('audited')                                       │
│  → BuildReportPdfJob::dispatch()  → queue:default                  │
└───────────────────────────┬─────────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────────────┐
│  BuildReportPdfJob::handle()                                        │
│  → ReportService::generatePdf()                                     │
│     ├── Blade render: resources/views/pdf/report.blade.php          │
│     ├── dompdf → PDF binary                                         │
│     └── Storage::put('pdfs/reports/audit-{slug}-{id}.pdf')         │
│  → Report::update(pdf_path, status: ready)                          │
│  → GenerateEmailJob::dispatch()  → queue:default                   │
└───────────────────────────┬─────────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────────────┐
│  GenerateEmailJob::handle()                                         │
│  → EmailService::generate()                                         │
│     ├── Build system prompt  (copywriter persona + JSON schema)     │
│     ├── Build user prompt    (3 worst checks, 2 best, top recs)    │
│     ├── POST /v1/messages  → Claude                                 │
│     ├── Parse JSON  {subject, html, plain}                          │
│     └── EmailLog::create()  [status: pending]                       │
│  → SendEmailJob::dispatch()  → queue:email                          │
└───────────────────────────┬─────────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────────────┐
│  SendEmailJob::handle()                                             │
│  → Check EmailLog::sentThisWeek() < WEEKLY_EMAIL_LIMIT (10)        │
│     └── If over limit: release(7 days)  ← re-queued for next week  │
│  → Mail::to()->send(new AuditOutreachMail($emailLog))               │
│     ├── View: resources/views/emails/outreach.blade.php             │
│     ├── Text: resources/views/emails/outreach-plain.blade.php       │
│     └── Attachment: storage/app/pdfs/reports/{filename}.pdf         │
│  → emailLog.markSent(providerMessageId)                             │
│  → business.markAs('emailed')                                       │
│  → campaign.refreshCounts()                                         │
└───────────────────────────┬─────────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────────────┐
│  DELIVERY TRACKING  (async, via Resend webhooks)                   │
│  POST /webhooks/resend                                              │
│  → WebhookController::resend()                                      │
│     ├── email.opened   → EmailLog.opened = true                    │
│     ├── email.clicked  → EmailLog.clicked = true                   │
│     ├── email.bounced  → EmailLog.status = bounced                 │
│     └── email.complained → EmailLog.status = bounced (critical log)│
└─────────────────────────────────────────────────────────────────────┘

## Scheduler (runs every minute via cron)

Monday 09:00  → audit:dispatch-weekly-emails   (picks pending EmailLogs up to limit)
Every hour    → audit:process-stalled          (re-queues stuck scraping/auditing jobs)
Sunday 02:00  → audit:prune-logs               (deletes job_logs older than 30 days)
Daily         → queue:prune-failed             (cleans failed_jobs table)

## Queue Workers

queue:scraping  (3 workers)  — ScrapeBusinessJob
queue:ai        (2 workers)  — GenerateReportJob
queue:email     (1 worker)   — SendEmailJob
queue:default   (2 workers)  — Everything else
```
