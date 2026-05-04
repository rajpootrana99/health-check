<?php

namespace App\Services;

use App\Models\Report;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ReportService
{
    // -----------------------------------------------------------------------
    // Public entry point
    // -----------------------------------------------------------------------

    /**
     * Generate a PDF for the given report, persist it to storage,
     * and update the report record with the path.
     *
     * Returns the storage-relative path to the PDF.
     */
    public function generatePdf(Report $report): string
    {
        $report->loadMissing('business');

        $viewData = $this->buildViewData($report);

        $pdf = Pdf::loadView('pdf.report', $viewData)
            ->setPaper('a4', 'portrait')
            ->setOption('dpi', 150)
            ->setOption('enable_remote', false)
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('isPhpEnabled', false)
            ->setOption('defaultFont', 'DejaVu Sans');

        $filename  = $this->buildFilename($report);
        $storagePath = config('audit.pdf.storage_path', 'pdfs/reports') . '/' . $filename;
        $disk      = config('audit.pdf.disk', 'local');

        Storage::disk($disk)->put($storagePath, $pdf->output());

        $report->update([
            'pdf_path'     => $storagePath,
            'status'       => 'ready',
            'generated_at' => now(),
        ]);

        Log::info("ReportService: PDF generated", [
            'report_id' => $report->id,
            'path'      => $storagePath,
            'size_kb'   => round(Storage::disk($disk)->size($storagePath) / 1024, 1),
        ]);

        return $storagePath;
    }

    // -----------------------------------------------------------------------
    // Build view data
    // -----------------------------------------------------------------------

    private function buildViewData(Report $report): array
    {
        $audit    = $report->audit_data ?? [];
        $business = $report->business;
        $checks   = $audit['checks'] ?? [];

        // Group checks into pass / warn / fail for the summary ring
        $passFail = [
            'pass' => 0,
            'warn' => 0,
            'fail' => 0,
        ];
        foreach ($checks as $check) {
            $status = $check['status'] ?? 'warn';
            $passFail[$status] = ($passFail[$status] ?? 0) + 1;
        }

        // Score colour
        $score      = $report->overall_score ?? 0;
        $scoreColor = match (true) {
            $score >= 70 => '#16a34a',   // green
            $score >= 45 => '#d97706',   // amber
            default      => '#dc2626',   // red
        };

        return [
            'report'       => $report,
            'business'     => $business,
            'audit'        => $audit,
            'checks'       => $checks,
            'passFail'     => $passFail,
            'score'        => $score,
            'scoreColor'   => $scoreColor,
            'summary'      => $audit['summary']         ?? '',
            'strengths'    => $audit['strengths']        ?? [],
            'weaknesses'   => $audit['weaknesses']       ?? [],
            'opportunities'=> $audit['opportunities']    ?? [],
            'recommendations' => $audit['recommendations'] ?? [],
            'generatedAt'  => now()->format('F j, Y'),
        ];
    }

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    private function buildFilename(Report $report): string
    {
        $slug = \Illuminate\Support\Str::slug(
            $report->business->name ?? 'business'
        );

        return "audit-{$slug}-{$report->id}.pdf";
    }
}
