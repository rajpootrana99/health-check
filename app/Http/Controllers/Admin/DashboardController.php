<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\Campaign;
use App\Models\EmailLog;
use App\Models\JobLog;
use App\Models\Report;

class DashboardController extends Controller
{
    public function index()
    {
        // ------------------------------------------------------------------
        // Pipeline counts
        // ------------------------------------------------------------------
        $pipeline = Business::query()
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $totals = [
            'businesses' => Business::count(),
            'audited'    => ($pipeline['audited']  ?? 0) + ($pipeline['emailed'] ?? 0),
            'emailed'    => $pipeline['emailed']   ?? 0,
            'failed'     => $pipeline['failed']    ?? 0,
        ];

        // ------------------------------------------------------------------
        // Weekly email stats
        // ------------------------------------------------------------------
        $weekLimit   = (int) config('audit.outreach.weekly_limit', 10);
        $weekSent    = EmailLog::sentThisWeek()->count();
        $weekOpened  = EmailLog::sentThisWeek()->where('opened', true)->count();
        $weekClicked = EmailLog::sentThisWeek()->where('clicked', true)->count();
        $openRate    = $weekSent > 0 ? round(($weekOpened  / $weekSent) * 100) : 0;
        $clickRate   = $weekSent > 0 ? round(($weekClicked / $weekSent) * 100) : 0;

        // ------------------------------------------------------------------
        // Recent campaigns
        // ------------------------------------------------------------------
        $recentCampaigns = Campaign::orderByDesc('created_at')->limit(5)->get();

        // ------------------------------------------------------------------
        // Reports ready but not yet emailed
        // ------------------------------------------------------------------
        $pendingOutreach = Business::readyForEmail()
            ->whereHas('latestReport', fn ($q) => $q->where('status', 'ready'))
            ->whereNotNull('email')
            ->count();

        // ------------------------------------------------------------------
        // Recent failures (last 24h)
        // ------------------------------------------------------------------
        $recentFailures = JobLog::where('status', 'failed')
            ->where('created_at', '>=', now()->subDay())
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        // ------------------------------------------------------------------
        // All-time open/click rates
        // ------------------------------------------------------------------
        $allTimeSent    = EmailLog::where('status', 'sent')->count();
        $allTimeOpened  = EmailLog::where('opened', true)->count();
        $allTimeClicked = EmailLog::where('clicked', true)->count();

        return view('admin.dashboard', compact(
            'pipeline',
            'totals',
            'weekLimit',
            'weekSent',
            'weekOpened',
            'weekClicked',
            'openRate',
            'clickRate',
            'recentCampaigns',
            'pendingOutreach',
            'recentFailures',
            'allTimeSent',
            'allTimeOpened',
            'allTimeClicked'
        ));
    }
}
