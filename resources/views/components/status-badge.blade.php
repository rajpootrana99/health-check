@props(['status'])

@php
$colours = [
    'pending'    => 'border-dark-muted/20 text-dark-muted',
    'fetching'   => 'border-blue-500/20 text-blue-400 bg-blue-500/5',
    'fetched'    => 'border-sky-500/20 text-sky-400 bg-sky-500/5',
    'scraping'   => 'border-primary/20 text-primary bg-primary/5',
    'scraped'    => 'border-amber-500/20 text-amber-400 bg-amber-500/5',
    'auditing'   => 'border-purple-500/20 text-purple-400 bg-purple-500/5',
    'audited'    => 'border-primary/20 text-primary bg-primary/5 shadow-[0_0_10px_rgba(250,222,1,0.1)]',
    'emailed'    => 'border-green-500/20 text-green-400 bg-green-500/5',
    'processing' => 'border-blue-500/20 text-blue-400 bg-blue-500/5',
    'completed'  => 'border-green-500/20 text-green-400 bg-green-500/5',
    'ready'      => 'border-green-500/20 text-green-400 bg-green-500/5',
    'generating' => 'border-primary/20 text-primary bg-primary/5 animate-pulse',
    'failed'     => 'border-red-500/20 text-red-400 bg-red-500/5 shadow-[0_0_10px_rgba(239,68,68,0.1)]',
];
$class = $colours[$status] ?? 'border-dark-muted/20 text-dark-muted';
@endphp

<span class="inline-flex items-center gap-1.5 border px-2 py-0.5 rounded text-[10px] font-black uppercase tracking-wider {{ $class }}">
    @if($status === 'generating' || $status === 'scraping' || $status === 'fetching')
        <div class="w-1 h-1 rounded-full bg-current animate-ping"></div>
    @endif
    {{ $status }}
</span>
