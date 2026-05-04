@extends('layouts.app')

@section('title', 'Reports — Audit Intel')
@section('header', 'Audit Intelligence')

@section('content')
<div class="card overflow-visible">
    @if($reports->isEmpty())
        <div class="text-center py-24 text-dark-muted font-bold uppercase tracking-[0.2em] text-xs">
            No audit telemetry initialized. Start a campaign to generate data.
        </div>
    @else
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-white/5">
                    <tr>
                        <th class="table-header">Business Profile</th>
                        <th class="table-header text-center">Efficiency Score</th>
                        <th class="table-header">Processing Status</th>
                        <th class="table-header">Generation Date</th>
                        <th class="table-header text-right">Telemetry Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-dark-border">
                    @foreach($reports as $report)
                    @php
                        $score = $report->overall_score;
                        $scoreColor = match(true) {
                            $score >= 70 => 'text-green-500',
                            $score >= 45 => 'text-primary',
                            default      => 'text-red-500',
                        };
                    @endphp
                    <tr class="hover:bg-white/5 transition-colors group">
                        <td class="px-6 py-5">
                            <p class="font-bold text-white tracking-tight group-hover:text-primary transition-colors">
                                {{ $report->business->name ?? '—' }}
                            </p>
                            <p class="text-[10px] text-dark-muted font-bold uppercase tracking-widest mt-1">
                                {{ $report->business->business_type }} &bull; {{ $report->business->location }}
                            </p>
                        </td>
                        <td class="px-6 py-5 text-center">
                            @if($score !== null)
                                <div class="inline-flex flex-col items-center">
                                    <span class="text-2xl font-bold {{ $scoreColor }} leading-none">{{ $score }}</span>
                                    <span class="text-[8px] font-bold text-dark-muted uppercase tracking-[0.2em] mt-1">/ 100</span>
                                </div>
                            @else
                                <span class="text-dark-muted">—</span>
                            @endif
                        </td>
                        <td class="px-6 py-5">
                            <x-status-badge :status="$report->status" />
                        </td>
                        <td class="px-6 py-5 text-dark-muted font-medium text-xs">
                            {{ $report->generated_at?->format('M d, Y') ?? 'Pending' }}
                        </td>
                        <td class="px-6 py-5 text-right space-x-2">
                            <a href="{{ route('admin.reports.show', $report) }}"
                               class="btn-secondary !inline-flex !py-1.5 !px-3 text-xs uppercase tracking-widest font-bold">Analysis</a>

                            @if($report->pdf_path)
                                <a href="{{ route('admin.reports.download', $report) }}"
                                   class="btn-primary !inline-flex !py-1.5 !px-3 text-xs uppercase tracking-widest font-bold">Artifact</a>
                            @endif

                            @if($report->status === 'ready' && $report->business->email && !$report->emailLogs()->exists())
                                <form action="{{ route('admin.reports.generate-email', $report) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="p-1.5 glass text-primary hover:bg-primary hover:text-dark transition-all rounded-lg">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                    </button>
                                </form>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="px-8 py-6 border-t border-dark-border">
            {{ $reports->links() }}
        </div>
    @endif
</div>
@endsection
