<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCampaignRequest;
use App\Jobs\FetchBusinessesJob;
use App\Models\Campaign;

class CampaignController extends Controller
{
    /**
     * Show the new campaign form (Step 11 will render the full admin layout).
     */
    public function create()
    {
        return view('admin.campaigns.create');
    }

    /**
     * Validate input, create campaign, dispatch the fetch job.
     */
    public function store(StoreCampaignRequest $request)
    {
        $campaign = Campaign::create([
            'business_type' => $request->business_type,
            'location'      => $request->location,
            'notes'         => $request->notes,
            'status'        => 'pending',
        ]);

        FetchBusinessesJob::dispatch($campaign)->onQueue('default');

        return redirect()
            ->route('admin.campaigns.show', $campaign)
            ->with('success', "Campaign \"{$campaign->label}\" created. Fetching businesses in the background…");
    }

    /**
     * Show a single campaign with its businesses.
     */
    public function show(Campaign $campaign)
    {
        $businesses = $campaign
            ->businesses()
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('admin.campaigns.show', compact('campaign', 'businesses'));
    }

    /**
     * List all campaigns.
     */
    public function index()
    {
        $campaigns = Campaign::orderByDesc('created_at')->paginate(15);

        return view('admin.campaigns.index', compact('campaigns'));
    }
}
