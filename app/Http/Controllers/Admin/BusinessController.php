<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\ScrapeBusinessJob;
use App\Jobs\GenerateReportJob;
use App\Models\Business;

class BusinessController extends Controller
{
    public function index()
    {
        $query = Business::with('campaign', 'latestReport')
            ->orderByDesc('created_at');

        // Status filter
        if ($status = request('status')) {
            $query->where('status', $status);
        }

        // Campaign filter
        if ($campaignId = request('campaign_id')) {
            $query->where('campaign_id', $campaignId);
        }

        // Search
        if ($search = request('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('website', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%");
            });
        }

        $businesses = $query->paginate(25)->withQueryString();

        $statusCounts = Business::query()
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        return view('admin.businesses.index', compact('businesses', 'statusCounts'));
    }

    public function show(Business $business)
    {
        $business->loadMissing(['campaign', 'reports', 'emailLogs', 'jobLogs']);
        return view('admin.businesses.show', compact('business'));
    }

    /**
     * Manually re-trigger scraping for a single business.
     */
    public function rescrape(Business $business)
    {
        abort_unless($business->website, 422, 'Business has no website URL.');

        $business->markAs('fetched', 'Manually re-queued for scraping');
        ScrapeBusinessJob::dispatch($business)->onQueue('scraping');

        return back()->with('success', "Scraping re-queued for {$business->name}.");
    }

    /**
     * Manually re-trigger AI audit for a single business.
     */
    public function reaudit(Business $business)
    {
        abort_unless($business->scraped_data, 422, 'Business has not been scraped yet.');

        $business->markAs('scraped', 'Manually re-queued for audit');
        GenerateReportJob::dispatch($business)->onQueue('ai');

        return back()->with('success', "AI audit re-queued for {$business->name}.");
    }
}
