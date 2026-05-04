@extends('layouts.app')

@section('title', $business->name)
@section('header', 'Business Details')

@section('content')

{{-- Actions --}}
<div class="flex flex-wrap gap-2 mb-8 justify-end mt-4">
    @if($business->website && in_array($business->status, ['fetched', 'failed', 'scraped', 'audited']))
    <form action="{{ route('admin.businesses.rescrape', $business) }}" method="POST">
        @csrf
        <button class="btn-secondary !py-2 !px-4 text-xs font-bold">
            Re-scan Website
        </button>
    </form>
    @endif

    @if($business->scraped_data && in_array($business->status, ['scraped', 'failed', 'audited']))
    <form action="{{ route('admin.businesses.reaudit', $business) }}" method="POST">
        @csrf
        <button class="btn-primary !py-2 !px-4 text-xs font-bold">
            Run Audit Again
        </button>
    </form>
    @endif
</div>

<div class="grid grid-cols-1 lg:grid-cols-12 gap-8">

    {{-- Info Column --}}
    <div class="lg:col-span-4 space-y-6">

        {{-- Basic Info --}}
        <div class="card p-6">
            <h2 class="text-xs font-bold text-primary uppercase mb-4">Business Info</h2>
            <dl class="space-y-3">
                <div class="flex justify-between">
                    <dt class="text-xs font-bold text-dark-muted">Type</dt>
                    <dd class="text-sm font-bold text-white">{{ $business->business_type }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-xs font-bold text-dark-muted">Location</dt>
                    <dd class="text-sm font-bold text-white">{{ $business->location }}</dd>
                </div>
                @if($business->phone)
                <div class="flex justify-between">
                    <dt class="text-xs font-bold text-dark-muted">Phone</dt>
                    <dd class="text-sm font-bold text-white">{{ $business->phone }}</dd>
                </div>
                @endif
                @if($business->website)
                <div class="flex flex-col gap-1 border-t border-dark-border pt-3 mt-3">
                    <dt class="text-xs font-bold text-dark-muted">Website</dt>
                    <dd>
                        <a href="{{ $business->website }}" target="_blank" class="text-sm font-bold text-blue-400 hover:underline break-all">
                            {{ $business->website }}
                        </a>
                    </dd>
                </div>
                @endif
                <div class="flex justify-between items-center pt-3 border-t border-dark-border mt-3">
                    <dt class="text-xs font-bold text-dark-muted">Status</dt>
                    <dd><x-status-badge :status="$business->status" /></dd>
                </div>
            </dl>
        </div>

        {{-- Ratings --}}
        <div class="card p-6">
            <h2 class="text-xs font-bold text-primary uppercase mb-4">Google Ratings</h2>
            <div class="flex items-center justify-between">
                <div>
                    <dt class="text-xs font-bold text-dark-muted mb-1">Rating</dt>
                    <dd class="text-2xl font-bold text-white">
                        @if($business->google_rating)
                            {{ number_format($business->google_rating, 1) }}<span class="text-primary text-sm ml-1">/ 5</span>
                        @else
                            —
                        @endif
                    </dd>
                </div>
                <div class="text-right">
                    <dt class="text-xs font-bold text-dark-muted mb-1">Reviews</dt>
                    <dd class="text-2xl font-bold text-white">{{ number_format($business->google_reviews_count) }}</dd>
                </div>
            </div>
        </div>

        {{-- Social --}}
        @if($business->hasSocialPresence())
        <div class="card p-6">
            <h2 class="text-xs font-bold text-primary uppercase mb-4">Social Media</h2>
            <div class="space-y-2">
                @foreach($business->socialLinks() as $platform => $url)
                <a href="{{ $url }}" target="_blank" class="flex items-center justify-between p-2 rounded-lg glass border-primary/5 hover:border-primary/20 text-xs font-bold text-white hover:text-primary transition-all">
                    <span class="uppercase tracking-tight">{{ $platform }}</span>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4"/></svg>
                </a>
                @endforeach
            </div>
        </div>
        @endif
    </div>

    {{-- Report Column --}}
    <div class="lg:col-span-8 space-y-6">
        @php $report = $business->latestReport; @endphp
        @if($report)
        <div class="card p-8">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-sm font-bold text-primary uppercase">Latest Report</h2>
                <div class="flex gap-4">
                    <a href="{{ route('admin.reports.show', $report) }}" class="text-xs font-bold text-blue-400 hover:underline">Full Report</a>
                    @if($report->pdf_path)
                    <a href="{{ route('admin.reports.download', $report) }}" class="text-xs font-bold text-green-500 hover:underline">Download PDF</a>
                    @endif
                </div>
            </div>

            @if($report->overall_score !== null)
            <div class="flex items-center gap-6 mb-6">
                <span class="text-6xl font-bold {{ $report->overall_score >= 70 ? 'text-green-500' : ($report->overall_score >= 45 ? 'text-primary' : 'text-red-500') }}">{{ $report->overall_score }}</span>
                <div>
                    <p class="text-sm font-bold text-white">Overall Audit Score</p>
                    <p class="text-xs font-medium text-dark-muted mt-0.5">Generated {{ $report->generated_at?->diffForHumans() }}</p>
                </div>
            </div>
            @if($report->summary)
            <p class="text-base text-white/90 leading-relaxed font-medium mb-8 border-l-4 border-primary/20 pl-4">
                {{ $report->summary }}
            </p>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-3">
                @foreach($report->healthChecks as $check)
                <div class="flex items-center gap-3 py-2 border-b border-dark-border/50">
                    <span class="w-2 h-2 rounded-full {{ $check['status'] === 'pass' ? 'bg-green-500' : ($check['status'] === 'warn' ? 'bg-primary' : 'bg-red-500') }}"></span>
                    <span class="text-xs font-bold text-dark-muted truncate">{{ $check['label'] ?? ucwords(str_replace('_', ' ', $check['name'])) }}</span>
                    <span class="ml-auto font-bold text-white text-xs">{{ $check['score'] }}</span>
                </div>
                @endforeach
            </div>
            @else
            <div class="py-12 text-center">
                <p class="text-sm font-bold text-dark-muted uppercase">Generating Report...</p>
            </div>
            @endif
        </div>
        @endif

        {{-- History --}}
        @if($business->jobLogs->isNotEmpty())
        <div class="card p-6">
            <h2 class="text-xs font-bold text-primary uppercase mb-4">Process History</h2>
            <div class="space-y-3">
                @foreach($business->jobLogs->sortByDesc('created_at')->take(5) as $log)
                <div class="flex items-center justify-between text-xs">
                    <span class="font-bold text-white">{{ class_basename($log->job_class) }}</span>
                    <div class="flex items-center gap-4">
                        <x-status-badge :status="$log->status" />
                        <span class="text-dark-muted font-medium">{{ $log->created_at->diffForHumans(null, true) }}</span>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
