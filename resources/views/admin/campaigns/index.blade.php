@extends('layouts.app')

@section('title', 'Campaigns')
@section('header', 'Campaign History')

@section('content')
<div class="mb-6 flex justify-end mt-4">
    <a href="{{ route('admin.campaigns.create') }}" class="btn-primary">
        + New Campaign
    </a>
</div>

<div class="card overflow-visible">
    @if($campaigns->isEmpty())
        <div class="text-center py-20 text-dark-muted font-bold text-sm">
            No campaigns yet. 
            <a href="{{ route('admin.campaigns.create') }}" class="text-primary hover:underline ml-1">Create one &rarr;</a>
        </div>
    @else
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-white/5">
                    <tr>
                        <th class="table-header">Campaign Name</th>
                        <th class="table-header">Status</th>
                        <th class="table-header text-center">Total</th>
                        <th class="table-header text-center">Audited</th>
                        <th class="table-header text-center">Sent</th>
                        <th class="table-header">Created</th>
                        <th class="table-header"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-dark-border">
                    @foreach($campaigns as $campaign)
                    <tr class="hover:bg-white/5 transition-colors group">
                        <td class="px-6 py-4">
                            <p class="font-bold text-white tracking-tight group-hover:text-primary transition-colors">
                                {{ $campaign->label }}
                            </p>
                        </td>
                        <td class="px-6 py-4">
                            <x-status-badge :status="$campaign->status" />
                        </td>
                        <td class="px-6 py-4 text-center text-white font-bold">{{ $campaign->total_businesses }}</td>
                        <td class="px-6 py-4 text-center text-blue-400 font-bold">{{ $campaign->audited_count }}</td>
                        <td class="px-6 py-4 text-center text-green-500 font-bold">{{ $campaign->emailed_count }}</td>
                        <td class="px-6 py-4 text-dark-muted text-xs">{{ $campaign->created_at->diffForHumans() }}</td>
                        <td class="px-6 py-4 text-right">
                            <a href="{{ route('admin.campaigns.show', $campaign) }}"
                               class="btn-secondary !inline-flex !py-1.5 !px-3 text-xs">View</a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="px-8 py-4 border-t border-dark-border">
            {{ $campaigns->links() }}
        </div>
    @endif
</div>
@endsection
