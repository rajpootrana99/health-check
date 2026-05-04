@extends('layouts.app')

@section('title', 'Queue Monitor — Business Audit')
@section('header', 'Queue Monitor')

@section('content')

{{-- Pipeline overview --}}
<div class="mb-6">
    <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-3">Pipeline Progress</h2>
    <div class="grid grid-cols-4 sm:grid-cols-8 gap-2">
        @foreach([
            'pending'  => ['label' => 'Pending',  'color' => 'bg-gray-100 text-gray-600'],
            'fetched'  => ['label' => 'Fetched',  'color' => 'bg-sky-100 text-sky-700'],
            'scraped'  => ['label' => 'Scraped',  'color' => 'bg-amber-100 text-amber-700'],
            'audited'  => ['label' => 'Audited',  'color' => 'bg-indigo-100 text-indigo-700'],
            'emailed'  => ['label' => 'Emailed',  'color' => 'bg-green-100 text-green-700'],
            'failed'   => ['label' => 'Failed',   'color' => 'bg-red-100 text-red-700'],
        ] as $status => $cfg)
        <div class="bg-white rounded-lg shadow-sm p-3 text-center">
            <p class="text-xl font-bold text-gray-800">{{ $pipelineStats->get($status, 0) }}</p>
            <span class="inline-block mt-1 rounded-full px-2 py-0.5 text-xs font-medium {{ $cfg['color'] }}">
                {{ $cfg['label'] }}
            </span>
        </div>
        @endforeach

        <div class="bg-white rounded-lg shadow-sm p-3 text-center">
            <p class="text-xl font-bold {{ $recentFailures > 0 ? 'text-red-600' : 'text-green-600' }}">
                {{ $recentFailures }}
            </p>
            <span class="inline-block mt-1 rounded-full px-2 py-0.5 text-xs font-medium bg-red-50 text-red-600">
                Errors 24h
            </span>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-3 text-center">
            <p class="text-xl font-bold text-indigo-600">
                {{ $weekStats['sent'] }}<span class="text-sm text-gray-400">/{{ $weekStats['limit'] }}</span>
            </p>
            <span class="inline-block mt-1 rounded-full px-2 py-0.5 text-xs font-medium bg-indigo-50 text-indigo-600">
                Sent/Week
            </span>
        </div>
    </div>
</div>

{{-- Job logs table --}}
<div class="bg-white shadow-sm rounded-lg overflow-hidden">
    <div class="px-4 py-3 border-b border-gray-200 flex justify-between items-center">
        <h2 class="text-sm font-semibold text-gray-700">Recent Job Logs</h2>
        <span class="text-xs text-gray-400">{{ $jobLogs->total() }} total entries</span>
    </div>

    @if($jobLogs->isEmpty())
        <div class="text-center py-12 text-gray-400 text-sm">No jobs logged yet.</div>
    @else
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">Job</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">Entity</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">Queue</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-500 uppercase tracking-wider">Duration</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500 uppercase tracking-wider">When</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($jobLogs as $log)
                    <tr class="hover:bg-gray-50 {{ $log->status === 'failed' ? 'bg-red-50' : '' }}">
                        <td class="px-4 py-3 font-medium text-gray-900 text-xs">
                            {{ class_basename($log->job_class) }}
                            @if($log->attempt > 1)
                                <span class="ml-1 text-amber-500 text-xs">(attempt {{ $log->attempt }})</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-gray-500 text-xs">
                            {{ $log->entity_type }}#{{ $log->entity_id }}
                        </td>
                        <td class="px-4 py-3 text-gray-500 text-xs">
                            {{ $log->queue }}
                        </td>
                        <td class="px-4 py-3">
                            <x-status-badge :status="$log->status" />
                            @if($log->status === 'failed' && $log->error)
                                <p class="text-xs text-red-600 mt-1 max-w-[220px]"
                                   title="{{ $log->error }}">
                                    {{ Str::limit($log->error, 80) }}
                                </p>
                            @endif
                            @if($log->status === 'completed' && $log->output)
                                <p class="text-xs text-gray-400 mt-1">{{ Str::limit($log->output, 60) }}</p>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right text-gray-500 text-xs font-mono">
                            @if($log->duration_ms !== null)
                                {{ $log->duration_ms >= 1000
                                    ? number_format($log->duration_ms / 1000, 1) . 's'
                                    : $log->duration_ms . 'ms' }}
                            @else
                                —
                            @endif
                        </td>
                        <td class="px-4 py-3 text-gray-400 text-xs">
                            {{ $log->created_at->diffForHumans() }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3 border-t border-gray-200">
            {{ $jobLogs->links() }}
        </div>
    @endif
</div>
@endsection
