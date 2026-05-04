@extends('layouts.app')

@section('title', 'Analysis — ' . $report->business->name)
@section('header', 'Audit Report')

@section('content')
@php
    $score      = $report->overall_score;
    $scoreColor = match(true) {
        $score >= 70 => 'text-green-500',
        $score >= 45 => 'text-primary',
        default      => 'text-red-500',
    };
    $checks = $report->healthChecks;
@endphp

{{-- Header Info --}}
<div class="flex flex-col lg:flex-row items-start lg:items-center justify-between mb-8 gap-6 mt-4">
    <div class="flex items-center gap-6">
        <div class="w-20 h-20 rounded-2xl glass flex flex-col items-center justify-center border-primary/20">
            <span class="text-3xl font-bold {{ $scoreColor }} leading-none">{{ $score ?? '00' }}</span>
            <span class="text-[10px] font-bold text-dark-muted uppercase mt-1">Score</span>
        </div>
        <div>
            <h2 class="text-2xl font-bold text-white tracking-tight">{{ $report->business->name }}</h2>
            <div class="flex flex-wrap items-center gap-2 text-sm font-medium text-dark-muted">
                <span>{{ $report->business->business_type }}</span>
                <span>&bull;</span>
                <span>{{ $report->business->location }}</span>
                @if($report->business->website)
                    <span>&bull;</span>
                    <a href="{{ $report->business->website }}" target="_blank" class="text-blue-400 hover:underline">
                        {{ parse_url($report->business->website, PHP_URL_HOST) }}
                    </a>
                @endif
            </div>
        </div>
    </div>

    <div class="flex flex-wrap gap-3 items-center w-full lg:w-auto">
        @if($report->status === 'ready' && $report->business->email && !$report->emailLogs()->exists())
            <form action="{{ route('admin.reports.generate-email', $report) }}" method="POST" class="flex-1 lg:flex-none">
                @csrf
                <button type="submit" class="btn-primary w-full">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    Draft Email
                </button>
            </form>
        @endif

        @if($report->pdf_path)
            <a href="{{ route('admin.reports.download', $report) }}" class="btn-secondary flex-1 lg:flex-none">
                <svg class="w-5 h-5 text-red-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd"/></svg>
                PDF Report
            </a>
        @endif

        <form action="{{ route('admin.reports.regenerate', $report) }}" method="POST" class="flex-1 lg:flex-none">
            @csrf
            <button class="btn-secondary w-full group">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                Regenerate
            </button>
        </form>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-12 gap-8 mb-8">
    {{-- Summary --}}
    <div class="lg:col-span-12 card p-8 border-l-8 border-primary/40">
        <h3 class="text-xs font-bold text-primary uppercase mb-4">Summary</h3>
        <p class="text-xl text-white leading-relaxed font-medium">
            {{ $report->summary }}
        </p>
    </div>

    {{-- Details --}}
    <div class="lg:col-span-8 space-y-8">
        <div class="card overflow-hidden">
            <div class="px-8 py-5 border-b border-dark-border bg-white/5">
                <h3 class="text-sm font-bold text-white uppercase">Audit Results</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="bg-dark-surface/50">
                            <th class="table-header">Check</th>
                            <th class="table-header">Findings</th>
                            <th class="table-header text-center">Score</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-dark-border">
                        @foreach($checks as $check)
                        @php
                            $pct = ($check['score'] / 10) * 100;
                            $color = match($check['status']) {
                                'pass'  => 'bg-green-500',
                                'warn'  => 'bg-primary',
                                default => 'bg-red-500',
                            };
                        @endphp
                        <tr class="hover:bg-white/5 transition-colors">
                            <td class="px-8 py-5 align-top">
                                <p class="font-bold text-white text-base mb-1">
                                    {{ $check['label'] ?? ucwords(str_replace('_', ' ', $check['name'])) }}
                                </p>
                                <x-status-badge :status="$check['status']" />
                            </td>
                            <td class="px-8 py-5 text-xs font-medium text-dark-muted leading-relaxed">
                                {{ $check['note'] ?? 'No issues found.' }}
                            </td>
                            <td class="px-8 py-5">
                                <div class="flex flex-col items-center gap-1">
                                    <span class="text-sm font-bold text-white">{{ $check['score'] }}/10</span>
                                    <div class="w-16 h-1.5 bg-dark-border rounded-full overflow-hidden">
                                        <div class="{{ $color }} h-1.5 rounded-full" style="width: {{ $pct }}%"></div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="lg:col-span-4 space-y-8">
        <div class="card p-8 border-t-4 border-green-500/30">
            <h3 class="text-xs font-bold text-green-500 uppercase mb-4">Strengths</h3>
            <ul class="space-y-3">
                @foreach($report->strengths as $item)
                <li class="flex gap-3 text-sm font-medium text-white/90 items-start">
                    <span class="text-green-500 font-bold">&plus;</span> {{ $item }}
                </li>
                @endforeach
            </ul>
        </div>

        <div class="card p-8 border-t-4 border-red-500/30">
            <h3 class="text-xs font-bold text-red-500 uppercase mb-4">Weaknesses</h3>
            <ul class="space-y-3">
                @foreach($report->weaknesses as $item)
                <li class="flex gap-3 text-sm font-medium text-white/90 items-start">
                    <span class="text-red-500 font-bold">&minus;</span> {{ $item }}
                </li>
                @endforeach
            </ul>
        </div>

        <div class="card p-8 border-t-4 border-primary/30">
            <h3 class="text-xs font-bold text-primary uppercase mb-6">Recommendations</h3>
            <div class="space-y-6">
                @foreach($report->recommendations as $rec)
                <div>
                    <div class="flex justify-between items-center mb-1">
                        <p class="text-sm font-bold text-white">{{ $rec['title'] }}</p>
                    </div>
                    <p class="text-xs font-medium text-dark-muted leading-relaxed">{{ $rec['description'] }}</p>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection
