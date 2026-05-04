<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\EmailLog;
use App\Models\JobLog;

class JobLogController extends Controller
{
    public function index()
    {
        $jobLogs = JobLog::orderByDesc('created_at')->paginate(30);

        // Pipeline snapshot for the summary cards
        $pipelineStats = Business::query()
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        // Recent failure count (last 24h)
        $recentFailures = JobLog::where('status', 'failed')
            ->where('created_at', '>=', now()->subDay())
            ->count();

        // Weekly email stats
        $weekStats = [
            'sent'    => EmailLog::sentThisWeek()->count(),
            'limit'   => (int) config('audit.outreach.weekly_limit', 10),
        ];

        return view('admin.job-logs.index', compact(
            'jobLogs',
            'pipelineStats',
            'recentFailures',
            'weekStats'
        ));
    }
}
