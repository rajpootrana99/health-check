<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<style>
/* ------------------------------------------------------------------ */
/* Base                                                                 */
/* ------------------------------------------------------------------ */
* { box-sizing: border-box; margin: 0; padding: 0; }

body {
    font-family: 'DejaVu Sans', Arial, sans-serif;
    font-size: 11px;
    color: #1e293b;
    background: #ffffff;
    line-height: 1.5;
}

/* ------------------------------------------------------------------ */
/* Layout helpers                                                       */
/* ------------------------------------------------------------------ */
.page-break { page-break-after: always; }

.container {
    width: 100%;
    padding: 0 36px;
}

.row {
    width: 100%;
    display: table;
    table-layout: fixed;
}
.col-half {
    display: table-cell;
    width: 50%;
    vertical-align: top;
    padding-right: 12px;
}
.col-half:last-child { padding-right: 0; padding-left: 12px; }

.col-third {
    display: table-cell;
    width: 33.333%;
    vertical-align: top;
    padding-right: 10px;
}
.col-third:last-child { padding-right: 0; }

/* ------------------------------------------------------------------ */
/* Header                                                               */
/* ------------------------------------------------------------------ */
.header {
    background: #4338ca;
    color: #ffffff;
    padding: 28px 36px 22px;
    margin-bottom: 24px;
}
.header-top {
    display: table;
    width: 100%;
    table-layout: fixed;
}
.header-left  { display: table-cell; vertical-align: top; }
.header-right {
    display: table-cell;
    vertical-align: top;
    text-align: right;
    width: 130px;
}

.report-label {
    font-size: 9px;
    font-weight: bold;
    letter-spacing: 2px;
    text-transform: uppercase;
    color: #a5b4fc;
    margin-bottom: 4px;
}
.business-name {
    font-size: 20px;
    font-weight: bold;
    color: #ffffff;
    margin-bottom: 4px;
    line-height: 1.2;
}
.business-meta {
    font-size: 10px;
    color: #c7d2fe;
}

/* Score circle */
.score-circle {
    width: 90px;
    height: 90px;
    border-radius: 50%;
    border: 5px solid rgba(255,255,255,0.3);
    text-align: center;
    padding-top: 18px;
    display: inline-block;
}
.score-number {
    font-size: 26px;
    font-weight: bold;
    color: #ffffff;
    line-height: 1;
}
.score-label {
    font-size: 9px;
    color: #c7d2fe;
    text-transform: uppercase;
    letter-spacing: 1px;
}

/* ------------------------------------------------------------------ */
/* Section headings                                                     */
/* ------------------------------------------------------------------ */
.section-heading {
    font-size: 11px;
    font-weight: bold;
    text-transform: uppercase;
    letter-spacing: 1.5px;
    color: #4338ca;
    border-bottom: 2px solid #e0e7ff;
    padding-bottom: 4px;
    margin-bottom: 12px;
}

/* ------------------------------------------------------------------ */
/* Summary box                                                          */
/* ------------------------------------------------------------------ */
.summary-box {
    background: #f8fafc;
    border-left: 4px solid #4338ca;
    padding: 14px 16px;
    margin-bottom: 22px;
    border-radius: 0 4px 4px 0;
}
.summary-text {
    font-size: 11px;
    color: #334155;
    line-height: 1.6;
}

/* ------------------------------------------------------------------ */
/* Score tiles (pass / warn / fail counts)                             */
/* ------------------------------------------------------------------ */
.tile {
    border-radius: 6px;
    padding: 12px 10px;
    text-align: center;
}
.tile-pass  { background: #f0fdf4; border: 1px solid #bbf7d0; }
.tile-warn  { background: #fffbeb; border: 1px solid #fde68a; }
.tile-fail  { background: #fef2f2; border: 1px solid #fecaca; }

.tile-number      { font-size: 22px; font-weight: bold; line-height: 1; margin-bottom: 2px; }
.tile-pass .tile-number  { color: #16a34a; }
.tile-warn .tile-number  { color: #d97706; }
.tile-fail .tile-number  { color: #dc2626; }
.tile-sublabel    { font-size: 9px; text-transform: uppercase; letter-spacing: 1px; color: #64748b; }

/* ------------------------------------------------------------------ */
/* Health checks table                                                  */
/* ------------------------------------------------------------------ */
.checks-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 22px;
    font-size: 10px;
}
.checks-table thead th {
    background: #4338ca;
    color: #ffffff;
    padding: 7px 10px;
    text-align: left;
    font-size: 9px;
    text-transform: uppercase;
    letter-spacing: 1px;
}
.checks-table thead th:last-child { text-align: center; }
.checks-table tbody tr:nth-child(even) { background: #f8fafc; }
.checks-table tbody td {
    padding: 7px 10px;
    border-bottom: 1px solid #f1f5f9;
    vertical-align: top;
}

.check-label {
    font-weight: bold;
    color: #1e293b;
    margin-bottom: 2px;
}
.check-note {
    color: #64748b;
    font-size: 9.5px;
    line-height: 1.4;
}

/* Score bar */
.score-bar-wrap {
    width: 80px;
    background: #e2e8f0;
    border-radius: 4px;
    height: 6px;
    margin: 0 auto 2px;
    overflow: hidden;
}
.score-bar-fill {
    height: 100%;
    border-radius: 4px;
}
.score-text {
    text-align: center;
    font-size: 9px;
    color: #64748b;
    font-weight: bold;
}

/* Status badge */
.badge {
    display: inline-block;
    border-radius: 10px;
    padding: 2px 8px;
    font-size: 8.5px;
    font-weight: bold;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.badge-pass { background: #dcfce7; color: #15803d; }
.badge-warn { background: #fef9c3; color: #a16207; }
.badge-fail { background: #fee2e2; color: #b91c1c; }

/* ------------------------------------------------------------------ */
/* Strengths / Weaknesses                                               */
/* ------------------------------------------------------------------ */
.sw-list { list-style: none; padding: 0; margin: 0 0 22px; }
.sw-list li {
    padding: 7px 10px 7px 28px;
    position: relative;
    border-radius: 4px;
    margin-bottom: 4px;
    font-size: 10.5px;
    line-height: 1.4;
}
.sw-list li::before {
    position: absolute;
    left: 10px;
    font-weight: bold;
    font-size: 12px;
}
.strength-list li { background: #f0fdf4; color: #166534; }
.strength-list li::before { content: "+"; color: #16a34a; }

.weakness-list li { background: #fef2f2; color: #991b1b; }
.weakness-list li::before { content: "−"; color: #dc2626; }

.opportunity-list li { background: #eff6ff; color: #1e40af; }
.opportunity-list li::before { content: "→"; color: #3b82f6; }

/* ------------------------------------------------------------------ */
/* Recommendations                                                      */
/* ------------------------------------------------------------------ */
.rec-card {
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    padding: 12px 14px;
    margin-bottom: 10px;
}
.rec-header {
    display: table;
    width: 100%;
    table-layout: fixed;
    margin-bottom: 5px;
}
.rec-title {
    display: table-cell;
    font-weight: bold;
    font-size: 11px;
    color: #1e293b;
}
.rec-priority {
    display: table-cell;
    text-align: right;
    width: 70px;
    vertical-align: top;
}
.rec-body { font-size: 10.5px; color: #475569; line-height: 1.5; }

.priority-high   { background: #fee2e2; color: #b91c1c; }
.priority-medium { background: #fef9c3; color: #a16207; }
.priority-low    { background: #f0f9ff; color: #0369a1; }

/* ------------------------------------------------------------------ */
/* Footer                                                               */
/* ------------------------------------------------------------------ */
.footer {
    margin-top: 30px;
    padding: 14px 36px;
    background: #f8fafc;
    border-top: 2px solid #e0e7ff;
    text-align: center;
    font-size: 9px;
    color: #94a3b8;
}
</style>
</head>
<body>

{{-- ================================================================ --}}
{{-- HEADER                                                            --}}
{{-- ================================================================ --}}
<div class="header">
    <div class="header-top">
        <div class="header-left">
            <div class="report-label">Digital Health Audit Report</div>
            <div class="business-name">{{ $business->name }}</div>
            <div class="business-meta">
                {{ $business->business_type }} &bull;
                {{ $business->location }}
                @if($business->website)
                    &bull; {{ parse_url($business->website, PHP_URL_HOST) }}
                @endif
            </div>
            <div class="business-meta" style="margin-top:6px;">
                Generated: {{ $generatedAt }}
                @if($business->google_rating)
                    &bull; Google Rating: {{ number_format($business->google_rating, 1) }}/5
                    ({{ number_format($business->google_reviews_count) }} reviews)
                @endif
            </div>
        </div>
        <div class="header-right">
            <div class="score-circle" style="border-color: {{ $scoreColor }};">
                <div class="score-number" style="color: {{ $scoreColor }};">{{ $score }}</div>
                <div class="score-label">/ 100</div>
            </div>
        </div>
    </div>
</div>

<div class="container">

{{-- ================================================================ --}}
{{-- EXECUTIVE SUMMARY                                                 --}}
{{-- ================================================================ --}}
<div class="section-heading">Executive Summary</div>
<div class="summary-box">
    <div class="summary-text">{{ $summary }}</div>
</div>

{{-- ================================================================ --}}
{{-- SCORE OVERVIEW TILES                                              --}}
{{-- ================================================================ --}}
<div class="section-heading">Health Check Overview</div>
<div class="row" style="margin-bottom: 22px;">
    <div class="col-third">
        <div class="tile tile-pass">
            <div class="tile-number">{{ $passFail['pass'] }}</div>
            <div class="tile-sublabel">Passing</div>
        </div>
    </div>
    <div class="col-third">
        <div class="tile tile-warn">
            <div class="tile-number">{{ $passFail['warn'] }}</div>
            <div class="tile-sublabel">Needs Work</div>
        </div>
    </div>
    <div class="col-third">
        <div class="tile tile-fail">
            <div class="tile-number">{{ $passFail['fail'] }}</div>
            <div class="tile-sublabel">Critical</div>
        </div>
    </div>
</div>

{{-- ================================================================ --}}
{{-- 15 HEALTH CHECKS TABLE                                           --}}
{{-- ================================================================ --}}
<table class="checks-table">
    <thead>
        <tr>
            <th style="width:28%;">Check</th>
            <th>Findings</th>
            <th style="width:90px; text-align:center;">Score</th>
        </tr>
    </thead>
    <tbody>
        @foreach($checks as $check)
        @php
            $label      = ucwords(str_replace('_', ' ', $check['name']));
            $barColor   = match($check['status']) {
                'pass'  => '#16a34a',
                'warn'  => '#d97706',
                default => '#dc2626',
            };
            $barWidth   = ($check['score'] / 10) * 100;
        @endphp
        <tr>
            <td>
                <div class="check-label">{{ $check['label'] ?? $label }}</div>
                <span class="badge badge-{{ $check['status'] }}">{{ ucfirst($check['status']) }}</span>
            </td>
            <td>
                <div class="check-note">{{ $check['note'] ?? '—' }}</div>
            </td>
            <td style="text-align:center; vertical-align:middle; padding-top:10px;">
                <div class="score-bar-wrap">
                    <div class="score-bar-fill"
                         style="width:{{ $barWidth }}%; background:{{ $barColor }};"></div>
                </div>
                <div class="score-text">{{ $check['score'] }}/10</div>
            </td>
        </tr>
        @endforeach
    </tbody>
</table>

<div class="page-break"></div>

{{-- ================================================================ --}}
{{-- STRENGTHS & WEAKNESSES                                            --}}
{{-- ================================================================ --}}
<div class="row">
    <div class="col-half">
        <div class="section-heading">Strengths</div>
        <ul class="sw-list strength-list">
            @forelse($strengths as $item)
            <li>{{ $item }}</li>
            @empty
            <li style="background:none; color:#94a3b8;">No strengths identified.</li>
            @endforelse
        </ul>
    </div>
    <div class="col-half">
        <div class="section-heading">Weaknesses</div>
        <ul class="sw-list weakness-list">
            @forelse($weaknesses as $item)
            <li>{{ $item }}</li>
            @empty
            <li style="background:none; color:#94a3b8;">No weaknesses identified.</li>
            @endforelse
        </ul>
    </div>
</div>

{{-- ================================================================ --}}
{{-- OPPORTUNITIES                                                     --}}
{{-- ================================================================ --}}
@if(!empty($opportunities))
<div class="section-heading">Opportunities</div>
<ul class="sw-list opportunity-list" style="margin-bottom:22px;">
    @foreach($opportunities as $item)
    <li>{{ $item }}</li>
    @endforeach
</ul>
@endif

{{-- ================================================================ --}}
{{-- RECOMMENDATIONS                                                   --}}
{{-- ================================================================ --}}
<div class="section-heading">Recommendations</div>

@forelse($recommendations as $rec)
@php
    $priority      = strtolower($rec['priority'] ?? 'medium');
    $priorityClass = "priority-{$priority}";
@endphp
<div class="rec-card">
    <div class="rec-header">
        <div class="rec-title">{{ $rec['title'] ?? 'Recommendation' }}</div>
        <div class="rec-priority">
            <span class="badge {{ $priorityClass }}">{{ ucfirst($priority) }}</span>
        </div>
    </div>
    <div class="rec-body">{{ $rec['description'] ?? '' }}</div>
</div>
@empty
<p style="color:#94a3b8; font-size:10px;">No recommendations generated.</p>
@endforelse

</div>{{-- /container --}}

{{-- ================================================================ --}}
{{-- FOOTER                                                            --}}
{{-- ================================================================ --}}
<div class="footer">
    This audit was generated automatically using AI analysis of publicly available data. &bull;
    {{ $business->name }} &bull; {{ $generatedAt }} &bull;
    Powered by Business Audit System
</div>

</body>
</html>
