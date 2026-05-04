@extends('layouts.app')

@section('title', 'New Campaign')
@section('header', 'Start Campaign')

@section('content')
<div class="max-w-xl mx-auto mt-8">
    <div class="card p-10">
        <div class="flex items-center gap-4 mb-8 border-b border-dark-border pb-6">
            <div class="w-12 h-12 rounded-xl bg-primary/10 flex items-center justify-center text-primary">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            </div>
            <div>
                <h2 class="text-lg font-bold text-white tracking-tight">Campaign Details</h2>
                <p class="text-xs font-medium text-dark-muted mt-0.5">Find businesses and run audits</p>
            </div>
        </div>

        <p class="text-sm text-white/70 leading-relaxed mb-8">
            Enter a business type and location. The system will find matching businesses, 
            scan their sites, and create audit reports for you to review.
        </p>

        <form action="{{ route('admin.campaigns.store') }}" method="POST" class="space-y-6">
            @csrf

            {{-- Business Type --}}
            <div>
                <label for="business_type" class="block text-xs font-bold text-dark-muted uppercase mb-2">
                    Business Type <span class="text-red-500">*</span>
                </label>
                <input
                    type="text"
                    id="business_type"
                    name="business_type"
                    value="{{ old('business_type') }}"
                    placeholder="e.g. Dentists, Plumbers"
                    autocomplete="off"
                    class="block w-full bg-dark-surface/50 border-dark-border rounded-xl text-sm font-semibold text-white
                           focus:ring-primary focus:border-primary placeholder:text-dark-muted py-3 px-4 transition-all"
                />
                @error('business_type')
                    <p class="mt-1 text-xs font-bold text-red-500">{{ $message }}</p>
                @enderror
            </div>

            {{-- Location --}}
            <div>
                <label for="location" class="block text-xs font-bold text-dark-muted uppercase mb-2">
                    Location <span class="text-red-500">*</span>
                </label>
                <input
                    type="text"
                    id="location"
                    name="location"
                    value="{{ old('location') }}"
                    placeholder="e.g. London, New York"
                    autocomplete="off"
                    class="block w-full bg-dark-surface/50 border-dark-border rounded-xl text-sm font-semibold text-white
                           focus:ring-primary focus:border-primary placeholder:text-dark-muted py-3 px-4 transition-all"
                />
                @error('location')
                    <p class="mt-1 text-xs font-bold text-red-500">{{ $message }}</p>
                @enderror
            </div>

            {{-- Notes --}}
            <div>
                <label for="notes" class="block text-xs font-bold text-dark-muted uppercase mb-2">
                    Notes <span class="text-dark-muted font-normal text-[10px]">(optional)</span>
                </label>
                <textarea
                    id="notes"
                    name="notes"
                    rows="2"
                    placeholder="Any internal notes..."
                    class="block w-full bg-dark-surface/50 border-dark-border rounded-xl text-sm font-semibold text-white
                           focus:ring-primary focus:border-primary placeholder:text-dark-muted py-3 px-4 transition-all"
                >{{ old('notes') }}</textarea>
            </div>

            <div class="pt-4">
                <button type="submit" class="btn-primary w-full py-3.5 text-sm font-bold">
                    Start Fetching &rarr;
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
