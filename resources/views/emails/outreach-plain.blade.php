{{ $business->name }} — Digital Audit Report
{{ $business->business_type }} · {{ $business->location }}

@if($emailLog->report && $emailLog->report->overall_score !== null)
OVERALL HEALTH SCORE: {{ $emailLog->report->overall_score }}/100
@endif

---

{!! strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $emailLog->body_plain ?? $emailLog->body_html)) !!}

---

BOOK A FREE 15-MINUTE CALL:
https://calendly.com/your-link

@if($emailLog->report && $emailLog->report->pdf_path)
Your full digital audit report is attached as a PDF.
@endif

---

You're receiving this because your business was included in our digital health audit
for {{ $business->business_type }}s in {{ $business->location }}.

To unsubscribe, reply with "unsubscribe" in the subject line.
Digital Audit Co. | In compliance with CAN-SPAM & GDPR.
