@extends('layouts.app')

@section('title', $campaign->label . ' — Business Audit')
@section('header', $campaign->label)

@section('content')

{{-- Campaign summary bar --}}
<div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
    @foreach([
        ['label' => 'Total',   'value' => $campaign->total_businesses],
        ['label' => 'Audited', 'value' => $campaign->audited_count],
        ['label' => 'Emailed', 'value' => $campaign->emailed_count],
        ['label' => 'Status',  'value' => ucfirst($campaign->status)],
    ] as $stat)
    <div class="bg-white rounded-lg shadow-sm p-4 text-center">
        <p class="text-2xl font-bold text-indigo-600">{{ $stat['value'] }}</p>
        <p class="text-xs text-gray-500 mt-1 uppercase tracking-wide">{{ $stat['label'] }}</p>
    </div>
    @endforeach
</div>

{{-- Businesses table --}}
<div class="bg-white shadow-sm rounded-lg overflow-hidden">
    <div class="px-4 py-3 border-b border-gray-200 flex justify-between items-center">
        <h2 class="text-sm font-semibold text-gray-700">Businesses</h2>
        <span class="text-xs text-gray-400">{{ $businesses->total() }} total</span>
    </div>

    @if($businesses->isEmpty())
        <div class="text-center py-12 text-gray-400 text-sm">
            No businesses fetched yet. Check the queue worker is running.
        </div>
    @else
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Name</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Website</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Email</th>
                        <th class="px-4 py-3 text-center font-medium text-gray-500">Rating</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($businesses as $business)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-medium text-gray-900 max-w-xs truncate">
                            {{ $business->name }}
                        </td>
                        <td class="px-4 py-3 text-gray-500 max-w-xs truncate">
                            @if($business->website)
                                <a href="{{ $business->website }}" target="_blank"
                                   class="text-indigo-600 hover:underline truncate block max-w-[180px]">
                                    {{ parse_url($business->website, PHP_URL_HOST) }}
                                </a>
                            @else
                                <span class="text-gray-300">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-gray-500">
                            {{ $business->email ?? '—' }}
                        </td>
                        <td class="px-4 py-3 text-center text-gray-600">
                            @if($business->google_rating)
                                {{ number_format($business->google_rating, 1) }}
                                <span class="text-yellow-400">&#9733;</span>
                            @else
                                —
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <x-status-badge :status="$business->status" />
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3 border-t border-gray-200">
            {{ $businesses->links() }}
        </div>
    @endif
</div>
@endsection
