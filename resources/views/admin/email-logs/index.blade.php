@extends('layouts.app')

@section('title', 'Emails')
@section('header', 'Email History')

@section('content')

{{-- Stats Grid --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-6 mb-8 mt-4">
    <div class="stat-card">
        <p class="text-xs font-bold text-dark-muted mb-2">Weekly Sent</p>
        <p class="text-3xl font-bold text-primary">{{ $weekStats['sent'] }}<span class="text-sm text-dark-muted font-medium ml-1">/ {{ $weekStats['limit'] }}</span></p>
    </div>
    <div class="stat-card">
        @php $remaining = max(0, $weekStats['limit'] - $weekStats['sent']); @endphp
        <p class="text-xs font-bold text-dark-muted mb-2">Available</p>
        <p class="text-3xl font-bold {{ $remaining > 0 ? 'text-green-500' : 'text-red-500' }}">{{ $remaining }}</p>
    </div>
    <div class="stat-card">
        <p class="text-xs font-bold text-dark-muted mb-2">Total Opened</p>
        <p class="text-3xl font-bold text-blue-500">{{ $weekStats['opened'] }}</p>
    </div>
    <div class="stat-card">
        <p class="text-xs font-bold text-dark-muted mb-2">Total Clicked</p>
        <p class="text-3xl font-bold text-purple-500">{{ $weekStats['clicked'] }}</p>
    </div>
</div>

{{-- Table --}}
<div class="card overflow-visible">
    @if($emailLogs->isEmpty())
        <div class="text-center py-20 text-dark-muted font-bold text-sm">No emails recorded yet.</div>
    @else
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="bg-white/5">
                        <th class="table-header">Business</th>
                        <th class="table-header">Subject</th>
                        <th class="table-header">Status</th>
                        <th class="table-header text-center">Engagement</th>
                        <th class="table-header text-center">Sent At</th>
                        <th class="table-header text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-dark-border">
                    @foreach($emailLogs as $log)
                    <tr class="hover:bg-white/5 transition-colors group">
                        <td class="px-6 py-5">
                            <p class="font-bold text-white tracking-tight">{{ $log->business->name ?? '—' }}</p>
                            <p class="text-xs text-dark-muted mt-1">{{ $log->recipient_email }}</p>
                        </td>
                        <td class="px-6 py-5">
                            <span class="text-dark-muted line-clamp-1 max-w-[240px]" title="{{ $log->subject }}">
                                {{ $log->subject }}
                            </span>
                        </td>
                        <td class="px-6 py-5">
                            <x-status-badge :status="$log->status" />
                            @if($log->failure_reason)
                                <p class="text-[10px] text-red-500 font-bold mt-1 max-w-[180px] truncate" title="{{ $log->failure_reason }}">
                                    {{ $log->failure_reason }}
                                </p>
                            @endif
                        </td>
                        <td class="px-6 py-5 text-center">
                            <div class="flex flex-col gap-1 items-center justify-center">
                                @if($log->opened)
                                    <span class="px-2 py-0.5 rounded bg-blue-500/10 text-blue-500 text-[10px] font-bold">Opened</span>
                                @endif
                                @if($log->clicked)
                                    <span class="px-2 py-0.5 rounded bg-purple-500/10 text-purple-500 text-[10px] font-bold">Clicked</span>
                                @endif
                                @if(!$log->opened && !$log->clicked)
                                    <span class="text-dark-muted text-xs">—</span>
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-5 text-center text-xs text-dark-muted">
                            {{ $log->sent_at?->format('M d, H:i') ?? '—' }}
                        </td>

                        <td class="px-6 py-5 text-center">
                            <div class="flex items-center justify-center gap-2">
                                <button type="button"
                                        onclick="openPreview({{ $log->id }}, {{ json_encode($log->subject) }}, {{ json_encode($log->report?->pdf_path ? route('admin.reports.download', $log->report) : null) }})"
                                        class="p-2 glass text-white hover:text-primary transition-colors rounded-lg">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                </button>

                                @if(in_array($log->status, ['pending', 'failed']))
                                    <form action="{{ route('admin.email-logs.send', $log) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="p-2 glass text-primary hover:bg-primary hover:text-dark transition-all rounded-lg">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="px-6 py-5 border-t border-dark-border">
            {{ $emailLogs->links() }}
        </div>
    @endif
</div>

{{-- Preview Modal --}}
<div id="previewModal"
     class="fixed inset-0 z-[100] hidden items-center justify-center bg-dark/90 backdrop-blur-md p-4"
     onclick="closePreview(event)">

    <div class="relative bg-dark-surface border border-dark-border rounded-2xl shadow-2xl w-full max-w-3xl flex flex-col overflow-hidden"
         onclick="event.stopPropagation()">

        <div class="flex items-center justify-between px-8 py-5 border-b border-dark-border bg-white/5">
            <div>
                <p class="text-[10px] font-bold text-primary mb-1 uppercase">Email Preview</p>
                <p id="modalSubject" class="text-base font-bold text-white truncate max-w-md"></p>
            </div>
            <button onclick="closePreviewModal()"
                    class="w-8 h-8 rounded-full glass flex items-center justify-center text-dark-muted hover:text-white">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <div id="pdfBadgeWrapper" class="hidden px-8 py-3 bg-primary/5 border-b border-primary/10">
            <a id="pdfBadgeLink" href="#" target="_blank"
               class="inline-flex items-center gap-2 text-primary hover:underline text-xs font-bold">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd"/></svg>
                PDF Report Attached
            </a>
        </div>

        <div class="flex-1 overflow-auto p-8 bg-dark">
            <div id="previewLoading" class="hidden flex-col items-center justify-center py-20 gap-3">
                <div class="w-10 h-10 border-4 border-dark-border border-t-primary rounded-full animate-spin"></div>
                <p class="text-xs text-dark-muted font-bold">Loading email content...</p>
            </div>
            <div id="previewBody" class="prose prose-invert prose-sm max-w-none text-dark-muted leading-relaxed">
                {{-- Content --}}
            </div>
        </div>

        <div class="px-8 py-4 border-t border-dark-border bg-dark-surface flex justify-end">
            <button onclick="closePreviewModal()" class="btn-secondary">Close</button>
        </div>
    </div>
</div>

<script>
function openPreview(logId, subject, pdfUrl) {
    document.getElementById('modalSubject').textContent = subject;
    const badgeWrapper = document.getElementById('pdfBadgeWrapper');
    const badgeLink    = document.getElementById('pdfBadgeLink');
    if (pdfUrl) {
        badgeLink.href = pdfUrl;
        badgeWrapper.classList.remove('hidden');
    } else {
        badgeWrapper.classList.add('hidden');
    }

    const modal = document.getElementById('previewModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');

    const body    = document.getElementById('previewBody');
    const spinner = document.getElementById('previewLoading');
    body.innerHTML = '';
    spinner.classList.remove('hidden');
    spinner.classList.add('flex');

    fetch('/admin/email-logs/' + logId + '/preview', {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(res => res.ok ? res.text() : Promise.reject('Fail'))
    .then(html => {
        body.innerHTML = html;
        spinner.classList.add('hidden');
    })
    .catch(err => {
        body.innerHTML = '<p class="text-red-500 text-center py-10">Failed to load email preview.</p>';
        spinner.classList.add('hidden');
    });
}
function closePreviewModal() {
    const modal = document.getElementById('previewModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    document.getElementById('previewBody').innerHTML = '';
}
function closePreview(event) {
    if (event.target === document.getElementById('previewModal')) closePreviewModal();
}
document.addEventListener('keydown', e => e.key === 'Escape' && closePreviewModal());
</script>
@endsection
