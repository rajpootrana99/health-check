@extends('layouts.app')

@section('title', 'Overview')
@section('header', 'Dashboard Overview')

@section('content')

{{-- Stats Grid --}}
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8 mt-4">

    <div class="stat-card flex flex-col justify-between">
        <div class="flex items-center justify-between mb-4">
            <div class="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center text-primary">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-2 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
            </div>
            <span class="badge border-primary/20 text-primary bg-primary/5">Active</span>
        </div>
        <div>
            <p class="text-xs font-semibold text-dark-muted mb-1">Total Businesses</p>
            <p class="text-3xl font-bold text-white">{{ number_format($totals['businesses']) }}</p>
        </div>
    </div>

    <div class="stat-card flex flex-col justify-between">
        <div class="flex items-center justify-between mb-4">
            <div class="w-10 h-10 rounded-xl bg-blue-500/10 flex items-center justify-center text-blue-500">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            @php $auditPct = $totals['businesses'] > 0 ? round(($totals['audited'] / $totals['businesses']) * 100) : 0; @endphp
            <span class="badge border-blue-500/20 text-blue-400 bg-blue-500/5">{{ $auditPct }}% Done</span>
        </div>
        <div>
            <p class="text-xs font-semibold text-dark-muted mb-1">Audited Reports</p>
            <p class="text-3xl font-bold text-white">{{ number_format($totals['audited']) }}</p>
        </div>
    </div>

    <div class="stat-card flex flex-col justify-between">
        <div class="flex items-center justify-between mb-4">
            <div class="w-10 h-10 rounded-xl bg-green-500/10 flex items-center justify-center text-green-500">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
            </div>
            <span class="badge border-green-500/20 text-green-400 bg-green-500/5">Sent</span>
        </div>
        <div>
            <p class="text-xs font-semibold text-dark-muted mb-1">Emails Sent</p>
            <p class="text-3xl font-bold text-white">{{ number_format($totals['emailed']) }}</p>
        </div>
    </div>

    <div class="stat-card flex flex-col justify-between">
        <div class="flex items-center justify-between mb-4">
            <div class="w-10 h-10 rounded-xl bg-red-500/10 flex items-center justify-center text-red-500">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            </div>
            <span class="badge border-red-500/20 text-red-400 bg-red-500/5">Alerts</span>
        </div>
        <div>
            <p class="text-xs font-semibold text-dark-muted mb-1">Failures</p>
            <p class="text-3xl font-bold {{ $totals['failed'] > 0 ? 'text-red-500' : 'text-white' }}">
                {{ number_format($totals['failed']) }}
            </p>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
    {{-- Email Stats --}}
    <div class="lg:col-span-1 card p-8">
        <div class="flex items-center justify-between mb-8">
            <h2 class="text-base font-bold text-white">Email Performance</h2>
            <div class="text-primary">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
            </div>
        </div>

        @php $pct = $weekLimit > 0 ? min(100, round(($weekSent / $weekLimit) * 100)) : 0; @endphp

        <div class="flex items-baseline gap-2 mb-2">
            <span class="text-5xl font-bold text-primary">{{ $weekSent }}</span>
            <span class="text-dark-muted font-bold text-xs">/ {{ $weekLimit }} THIS WEEK</span>
        </div>

        <div class="w-full bg-dark-border rounded-full h-3 mb-8">
            <div class="h-3 rounded-full transition-all duration-1000 ease-out {{ $pct >= 100 ? 'bg-green-500' : 'bg-primary' }}"
                 style="width: {{ $pct }}%"></div>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div class="glass border-blue-500/10 rounded-2xl p-4 text-center">
                <p class="text-xl font-bold text-blue-500">{{ $openRate }}%</p>
                <p class="text-[10px] font-bold text-dark-muted uppercase mt-1">Open Rate</p>
            </div>
            <div class="glass border-purple-500/10 rounded-2xl p-4 text-center">
                <p class="text-xl font-bold text-purple-500">{{ $clickRate }}%</p>
                <p class="text-[10px] font-bold text-dark-muted uppercase mt-1">Click Rate</p>
            </div>
        </div>
    </div>

    {{-- Status Table --}}
    <div class="lg:col-span-1 card p-8">
        <h2 class="text-base font-bold text-white mb-6">Pipeline Status</h2>
        @php
            $stages = [
                ['key' => 'pending',  'label' => 'New',       'color' => 'bg-dark-muted'],
                ['key' => 'fetched',  'label' => 'Found',     'color' => 'bg-sky-400'],
                ['key' => 'scraped',  'label' => 'Scraped',   'color' => 'bg-amber-400'],
                ['key' => 'audited',  'label' => 'Audited',   'color' => 'bg-primary'],
                ['key' => 'emailed',  'label' => 'Sent',      'color' => 'bg-green-500'],
                ['key' => 'failed',   'label' => 'Failed',    'color' => 'bg-red-500'],
            ];
            $maxCount = max(1, $pipeline->max());
        @endphp
        <div class="space-y-4">
            @foreach($stages as $stage)
            @php $count = $pipeline->get($stage['key'], 0); @endphp
            <div>
                <div class="flex justify-between items-center mb-1">
                    <span class="text-xs font-semibold text-dark-muted">{{ $stage['label'] }}</span>
                    <span class="text-xs font-bold text-white">{{ number_format($count) }}</span>
                </div>
                <div class="w-full bg-dark-border rounded-full h-1.5 overflow-hidden">
                    <div class="{{ $stage['color'] }} h-1.5 rounded-full"
                         style="width: {{ $maxCount > 0 ? round(($count / $maxCount) * 100) : 0 }}%"></div>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    {{-- All Time --}}
    <div class="lg:col-span-1 card p-8">
        <h2 class="text-base font-bold text-white mb-8">Overall Stats</h2>
        @php
            $allOpenRate  = $allTimeSent > 0 ? round(($allTimeOpened  / $allTimeSent) * 100) : 0;
            $allClickRate = $allTimeSent > 0 ? round(($allTimeClicked / $allTimeSent) * 100) : 0;
        @endphp
        <div class="space-y-6">
            <div class="flex flex-col">
                <span class="text-4xl font-bold text-white">{{ number_format($allTimeSent) }}</span>
                <span class="text-xs font-semibold text-dark-muted mt-1">Total Emails Sent</span>
            </div>
            <div class="space-y-4 border-t border-dark-border pt-6">
                <div>
                    <div class="flex justify-between text-xs font-semibold mb-2">
                        <span class="text-dark-muted">Open Rate</span>
                        <span class="text-blue-500">{{ $allOpenRate }}%</span>
                    </div>
                    <div class="w-full bg-dark-border rounded-full h-1">
                        <div class="bg-blue-500 h-1 rounded-full" style="width: {{ $allOpenRate }}%"></div>
                    </div>
                </div>
                <div>
                    <div class="flex justify-between text-xs font-semibold mb-2">
                        <span class="text-dark-muted">Click Rate</span>
                        <span class="text-purple-500">{{ $allClickRate }}%</span>
                    </div>
                    <div class="w-full bg-dark-border rounded-full h-1">
                        <div class="bg-purple-500 h-1 rounded-full" style="width: {{ $allClickRate }}%"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
    <div class="card">
        <div class="px-6 py-5 border-b border-dark-border flex justify-between items-center">
            <h2 class="text-base font-bold text-white">Recent Campaigns</h2>
            <a href="{{ route('admin.campaigns.index') }}" class="text-primary text-xs font-semibold hover:underline">View All</a>
        </div>
        <div class="divide-y divide-dark-border">
            @forelse($recentCampaigns as $campaign)
            <div class="px-6 py-4 flex items-center justify-between hover:bg-white/5 transition-colors">
                <div>
                    <p class="text-sm font-bold text-white">{{ $campaign->label }}</p>
                    <p class="text-xs text-dark-muted mt-1">
                        {{ $campaign->total_businesses }} items &bull; {{ $campaign->created_at->diffForHumans() }}
                    </p>
                </div>
                <x-status-badge :status="$campaign->status" />
            </div>
            @empty
            <div class="px-6 py-10 text-center text-dark-muted text-sm">No campaigns yet.</div>
            @endforelse
        </div>
    </div>

    <div class="card">
        <div class="px-6 py-5 border-b border-dark-border flex justify-between items-center">
            <h2 class="text-base font-bold text-white">System Alerts</h2>
            <a href="{{ route('admin.queue-monitor.index') }}" class="text-primary text-xs font-semibold hover:underline">Logs</a>
        </div>
        <div class="divide-y divide-dark-border">
            @forelse($recentFailures as $failure)
            <div class="px-6 py-4 flex items-center justify-between hover:bg-white/5 transition-colors">
                <div class="min-w-0 pr-4">
                    <p class="text-sm font-bold text-white mb-1">
                        {{ class_basename($failure->job_class) }}
                    </p>
                    <p class="text-xs text-red-500 truncate" title="{{ $failure->error }}">
                        {{ $failure->error ?? 'System error' }}
                    </p>
                </div>
                <span class="text-xs text-dark-muted shrink-0">{{ $failure->created_at->diffForHumans(null, true) }}</span>
            </div>
            @empty
            <div class="px-6 py-10 text-center text-green-500/50 text-sm">System Healthy</div>
            @endforelse
        </div>
    </div>
</div>
@endsection
