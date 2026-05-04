<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>@yield('title', 'Admin Dashboard')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-dark text-white font-sans antialiased">

<div class="min-h-full flex flex-col lg:flex-row">

    {{-- Sidebar --}}
    <aside class="w-full lg:w-64 lg:fixed lg:inset-y-0 glass z-20 flex flex-col">
        <div class="flex items-center h-20 px-6 shrink-0 border-b border-dark-border">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-primary rounded-xl flex items-center justify-center shadow-lg">
                    <svg class="w-6 h-6 text-dark" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                </div>
                <div>
                    <span class="text-white font-bold text-lg block leading-none">Dashboard</span>
                    <span class="text-primary font-medium text-[10px] block mt-1">Admin Panel</span>
                </div>
            </div>
        </div>

        <nav class="flex-1 overflow-y-auto px-4 py-6 space-y-2">
            @php
                $navItems = [
                    ['route' => 'admin.dashboard',         'label' => 'Overview',      'icon' => 'M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z'],
                    ['route' => 'admin.campaigns.index',   'label' => 'Campaigns',     'icon' => 'M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z'],
                    ['route' => 'admin.businesses.index',  'label' => 'Businesses',    'icon' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-2 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4'],
                    ['route' => 'admin.reports.index',     'label' => 'Audit Reports', 'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
                    ['route' => 'admin.email-logs.index',  'label' => 'Emails',        'icon' => 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z'],
                    ['route' => 'admin.queue-monitor.index','label' => 'System Logs',  'icon' => 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
                ];
            @endphp

            @foreach($navItems as $item)
            @php
                $active = request()->routeIs($item['route'] . '*') ||
                          ($item['route'] === 'admin.dashboard' && request()->routeIs('admin.dashboard'));
            @endphp
            <a href="{{ route($item['route']) }}"
               class="group flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-semibold transition-all
                      {{ $active
                           ? 'bg-primary text-dark shadow-md'
                           : 'text-dark-muted hover:bg-dark-surface hover:text-white' }}">
                <svg class="h-5 w-5 shrink-0 {{ $active ? 'text-dark' : 'text-dark-muted group-hover:text-primary' }}"
                     fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}" />
                </svg>
                {{ $item['label'] }}
            </a>
            @endforeach
        </nav>

        <div class="p-4 border-t border-dark-border">
            <a href="{{ route('admin.campaigns.create') }}" class="btn-primary w-full py-3 text-xs">
                + New Campaign
            </a>
        </div>
    </aside>

    {{-- Main content --}}
    <div class="flex flex-col flex-1 lg:pl-64 min-h-screen">

        {{-- Top bar --}}
        <header class="h-20 flex items-center justify-between px-8 border-b border-dark-border">
            <div>
                <h1 class="text-xl font-bold text-white">
                    @yield('header', 'Overview')
                </h1>
                <p class="text-xs text-dark-muted mt-1">
                    {{ now()->format('F d, Y') }}
                </p>
            </div>

            <div class="flex items-center gap-3 px-4 py-2 glass rounded-full border-primary/10 bg-primary/5">
                <div class="w-2 h-2 bg-primary rounded-full animate-pulse shadow-[0_0_8px_#fade01]"></div>
                <span class="text-[10px] font-bold text-white">System Active</span>
            </div>
        </header>

        <div class="px-8 pt-4">
            @if(session('success'))
                <div class="rounded-xl glass border-green-500/20 bg-green-500/5 px-6 py-4 mb-6 flex items-center gap-3">
                    <div class="text-green-500">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                    </div>
                    <p class="text-sm font-semibold text-green-400">{{ session('success') }}</p>
                </div>
            @endif
        </div>

        <main class="flex-1 px-8 pb-12">
            @yield('content')
        </main>

    </div>
</div>

</body>
</html>
