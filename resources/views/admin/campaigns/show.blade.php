@extends('layouts.app')

@section('title', $campaign->label)
@section('header', 'Campaign Details')

@section('content')

{{-- Stats Grid --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-6 mb-8 mt-4">
    <div class="stat-card">
        <p class="text-xs font-bold text-dark-muted mb-2 uppercase">Total Items</p>
        <p class="text-3xl font-bold text-white">{{ $campaign->total_businesses }}</p>
    </div>
    <div class="stat-card">
        <p class="text-xs font-bold text-dark-muted mb-2 uppercase">Audited</p>
        <p class="text-3xl font-bold text-primary">{{ $campaign->audited_count }}</p>
    </div>
    <div class="stat-card">
        <p class="text-xs font-bold text-dark-muted mb-2 uppercase">Emails Sent</p>
        <p class="text-3xl font-bold text-green-500">{{ $campaign->emailed_count }}</p>
    </div>
    <div class="stat-card">
        <p class="text-xs font-bold text-dark-muted mb-2 uppercase">Status</p>
        <div class="mt-1">
            <x-status-badge :status="$campaign->status" />
        </div>
    </div>
</div>

{{-- Businesses Table --}}
<div class="card overflow-visible">
    <div class="px-6 py-5 border-b border-dark-border flex justify-between items-center bg-white/5">
        <h2 class="text-base font-bold text-white">Businesses in this Campaign</h2>
        <span class="text-xs font-bold text-dark-muted">{{ $businesses->total() }} TOTAL</span>
    </div>

    @if($businesses->isEmpty())
        <div class="text-center py-20 text-dark-muted font-bold text-sm">
            No businesses fetched yet. Ensure the background worker is running.
        </div>
    @else
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="bg-white/5">
                        <th class="table-header">Business Name</th>
                        <th class="table-header">Website</th>
                        <th class="table-header">Email Address</th>
                        <th class="table-header text-center">Rating</th>
                        <th class="table-header">Current Status</th>
                        <th class="table-header text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-dark-border">
                    @foreach($businesses as $business)
                    <tr class="hover:bg-white/5 transition-colors group">
                        <td class="px-6 py-5">
                            <p class="font-bold text-white tracking-tight group-hover:text-primary transition-colors">
                                {{ $business->name }}
                            </p>
                        </td>
                        <td class="px-6 py-5">
                            @if($business->website)
                                <a href="{{ $business->website }}" target="_blank"
                                   class="text-blue-400 hover:underline font-semibold truncate block max-w-[200px]">
                                    {{ parse_url($business->website, PHP_URL_HOST) }}
                                </a>
                            @else
                                <span class="text-dark-muted text-xs italic">No website</span>
                            @endif
                        </td>
                        <td class="px-6 py-5">
                            <span class="text-white/80 font-medium">{{ $business->email ?? '—' }}</span>
                        </td>
                        <td class="px-6 py-5 text-center">
                            @if($business->google_rating)
                                <div class="inline-flex items-center gap-1">
                                    <span class="text-sm font-bold text-white">{{ number_format($business->google_rating, 1) }}</span>
                                    <span class="text-yellow-400 text-xs">★</span>
                                </div>
                            @else
                                <span class="text-dark-muted">—</span>
                            @endif
                        </td>
                        <td class="px-6 py-5">
                            <x-status-badge :status="$business->status" />
                        </td>
                        <td class="px-6 py-5 text-right">
                            <a href="{{ route('admin.businesses.show', $business) }}"
                               class="btn-secondary !inline-flex !py-1.5 !px-3 text-xs">View</a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="px-8 py-5 border-t border-dark-border">
            {{ $businesses->links() }}
        </div>
    @endif
</div>
@endsection
