@extends('layouts.app')

@section('title', 'Businesses')
@section('header', 'Business List')

@section('content')

{{-- Filter Grid --}}
<div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 mb-6 mt-4">
    <div class="flex flex-wrap gap-2">
        @php
            $statuses = ['', 'pending', 'fetched', 'scraped', 'audited', 'emailed', 'failed'];
            $labels   = ['' => 'All', 'pending' => 'Pending', 'fetched' => 'Found',
                         'scraped' => 'Scraped', 'audited' => 'Audited',
                         'emailed' => 'Sent', 'failed' => 'Failed'];
        @endphp
        @foreach($statuses as $s)
        @php
            $isActive = request('status', '') === $s;
            $count    = $s ? ($statusCounts->get($s, 0)) : $statusCounts->sum();
        @endphp
        <a href="{{ route('admin.businesses.index', array_merge(request()->except('page'), ['status' => $s])) }}"
           class="inline-flex items-center gap-2 rounded-lg px-3 py-1.5 text-xs font-bold border transition-all
                  {{ $isActive
                       ? 'bg-primary text-dark border-primary'
                       : 'glass text-dark-muted border-dark-border hover:text-white' }}">
            {{ $labels[$s] }}
            <span class="rounded-md px-1.5 py-0.5 text-[10px]
                         {{ $isActive ? 'bg-dark text-primary' : 'bg-dark-border text-dark-muted' }}">
                {{ $count }}
            </span>
        </a>
        @endforeach
    </div>

    {{-- Search --}}
    <div class="w-full lg:w-72">
        <form method="GET" action="{{ route('admin.businesses.index') }}" class="relative group">
            @if(request('status'))
                <input type="hidden" name="status" value="{{ request('status') }}" />
            @endif
            <input type="text" name="search" value="{{ request('search') }}"
                   placeholder="Search..."
                   class="w-full bg-dark-surface/50 border-dark-border rounded-xl text-sm font-semibold text-white
                          focus:ring-primary focus:border-primary placeholder:text-dark-muted py-2 px-4 pr-10 transition-all" />
            <button type="submit" class="absolute right-3 top-1/2 -translate-y-1/2 text-dark-muted">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </button>
        </form>
    </div>
</div>

{{-- Table --}}
<div class="card">
    @if($businesses->isEmpty())
        <div class="text-center py-20 text-dark-muted font-bold text-sm">No businesses found.</div>
    @else
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="bg-white/5">
                        <th class="table-header">Business</th>
                        <th class="table-header">Website</th>
                        <th class="table-header text-center">Rating</th>
                        <th class="table-header text-center">Score</th>
                        <th class="table-header">Status</th>
                        <th class="table-header text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-dark-border">
                    @foreach($businesses as $business)
                    @php
                        $score  = $business->latestReport?->overall_score;
                        $scoreColor = match(true) {
                            $score === null    => 'text-dark-muted',
                            $score >= 70       => 'text-green-500',
                            $score >= 45       => 'text-primary',
                            default            => 'text-red-500',
                        };
                    @endphp
                    <tr class="hover:bg-white/5 transition-colors group">
                        <td class="px-6 py-4">
                            <p class="font-bold text-white tracking-tight">{{ $business->name }}</p>
                            <p class="text-xs text-dark-muted mt-0.5">
                                {{ $business->business_type }} &bull; {{ $business->location }}
                            </p>
                        </td>
                        <td class="px-6 py-4">
                            @if($business->website)
                                <a href="{{ $business->website }}" target="_blank"
                                   class="text-blue-400 hover:underline text-sm font-semibold truncate block max-w-[200px]">
                                    {{ parse_url($business->website, PHP_URL_HOST) }}
                                </a>
                            @else
                                <span class="text-dark-muted text-xs italic">No website</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-center">
                            @if($business->google_rating)
                                <div class="inline-flex items-center gap-1">
                                    <span class="text-sm font-bold text-white">{{ number_format($business->google_rating, 1) }}</span>
                                    <span class="text-yellow-400 text-xs">★</span>
                                    <span class="text-dark-muted text-xs">({{ $business->google_reviews_count }})</span>
                                </div>
                            @else
                                <span class="text-dark-muted">—</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-center">
                            @if($score !== null)
                                <span class="text-base font-bold {{ $scoreColor }}">{{ $score }}</span>
                            @else
                                <span class="text-dark-muted">—</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <x-status-badge :status="$business->status" />
                        </td>
                        <td class="px-6 py-4 text-right">
                            <a href="{{ route('admin.businesses.show', $business) }}"
                               class="btn-secondary !inline-flex !py-1.5 !px-3 text-xs">View</a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t border-dark-border flex items-center justify-between">
            <p class="text-xs text-dark-muted font-bold">
                Showing {{ $businesses->firstItem() }}–{{ $businesses->lastItem() }}
                of {{ $businesses->total() }}
            </p>
            {{ $businesses->links() }}
        </div>
    @endif
</div>
@endsection
