<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\BuildReportPdfJob;
use App\Jobs\GenerateEmailJob;
use App\Models\Report;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class ReportController extends Controller
{
    /**
     * List all ready reports.
     */
    public function index()
    {
        $reports = Report::with('business')
            ->whereIn('status', ['ready', 'generating', 'failed'])
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('admin.reports.index', compact('reports'));
    }

    /**
     * Show a single report (detail view).
     */
    public function show(Report $report)
    {
        $report->loadMissing('business');
        return view('admin.reports.show', compact('report'));
    }

    /**
     * Stream the PDF to the browser.
     */
    public function download(Report $report): Response
    {
        abort_unless($report->pdf_path && Storage::exists($report->pdf_path), 404);

        $filename = basename($report->pdf_path);

        return response(
            Storage::get($report->pdf_path),
            200,
            [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            ]
        );
    }

    /**
     * Manually trigger PDF re-generation (admin action).
     */
    public function regenerate(Report $report)
    {
        $report->update(['status' => 'generating', 'pdf_path' => null]);

        BuildReportPdfJob::dispatch($report)->onQueue('default');

        return back()->with('success', 'PDF regeneration queued.');
    }

    /**
     * Manually compose an outreach email draft for this report.
     * Only allowed when the report is ready and no email has been drafted yet.
     */
    public function generateEmail(Report $report)
    {
        abort_unless($report->status === 'ready', 422, 'Report is not ready yet.');
        abort_unless($report->business->email, 422, 'This business has no email address on record.');

        $existing = $report->emailLogs()->whereIn('status', ['pending', 'queued', 'sent'])->exists();
        abort_if($existing, 422, 'An email draft or sent email already exists for this report.');

        GenerateEmailJob::dispatch($report->fresh())->onQueue('default');

        return back()->with('success', 'Email draft generation queued. Check Email Logs shortly.');
    }
}
