<?php

/**
 * ============================================================
 *  MODEL RELATIONSHIP MAP
 * ============================================================
 *
 *  Campaign  ──hasMany──►  Business  ──hasMany──►  Report
 *                │                   ──hasMany──►  EmailLog
 *                │                   ──hasMany──►  JobLog (entity_type='business')
 *                │
 *                └── refreshCounts() denormalizes audited_count / emailed_count
 *
 *
 *  Business
 *   ├── campaign_id    (FK → campaigns)
 *   ├── status         (state machine)
 *   │     pending → fetched → scraping → scraped
 *   │                                  → auditing → audited → emailed
 *   │                                  → failed (at any point)
 *   ├── scraped_data   (JSON, set by ScraperService)
 *   └── markAs(status) helper
 *
 *  Report
 *   ├── business_id    (FK → businesses)
 *   ├── audit_data     (JSON — 15 checks + summary + recommendations)
 *   ├── overall_score  (0–100, denormalized from audit_data)
 *   ├── pdf_path       (storage-relative)
 *   └── Accessors: healthChecks, strengths, weaknesses, recommendations
 *
 *  EmailLog
 *   ├── business_id    (FK → businesses)
 *   ├── report_id      (FK → reports, nullable)
 *   ├── status         pending → queued → sent | failed | bounced
 *   ├── week_number + week_year  → used for 10/week enforcement
 *   └── markSent() / markFailed() helpers
 *
 *  JobLog
 *   ├── entity_type + entity_id  (no FK — survives deletions)
 *   ├── job_class      (fully qualified class name)
 *   └── start() / finish() / fail() static helpers
 *
 * ============================================================
 */
