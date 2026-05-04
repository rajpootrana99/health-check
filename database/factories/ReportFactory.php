<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\Report;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReportFactory extends Factory
{
    protected $model = Report::class;

    private static array $healthCheckNames = [
        'website_speed',
        'mobile_responsiveness',
        'seo_optimization',
        'google_reviews_rating',
        'social_media_presence',
        'posting_frequency',
        'branding_consistency',
        'call_to_action_clarity',
        'lead_capture_forms',
        'conversion_optimization',
        'content_quality',
        'paid_ads_presence',
        'email_marketing_usage',
        'trust_signals',
        'technical_performance',
    ];

    public function definition(): array
    {
        $score = $this->faker->numberBetween(30, 95);

        return [
            'business_id'     => Business::factory()->scraped(),
            'audit_data'      => $this->fakeAuditData($score),
            'overall_score'   => $score,
            'summary'         => $this->faker->paragraph(3),
            'pdf_path'        => null,
            'pdf_url'         => null,
            'status'          => 'ready',
            'ai_model_used'   => 'claude-opus-4-6',
            'ai_tokens_used'  => $this->faker->numberBetween(1200, 3800),
            'generation_error'=> null,
            'generated_at'    => now()->subMinutes($this->faker->numberBetween(1, 120)),
        ];
    }

    // -----------------------------------------------------------------------
    // States
    // -----------------------------------------------------------------------

    public function pending(): static
    {
        return $this->state([
            'status'        => 'pending',
            'audit_data'    => null,
            'overall_score' => null,
            'summary'       => null,
            'generated_at'  => null,
        ]);
    }

    public function failed(): static
    {
        return $this->state([
            'status'           => 'failed',
            'audit_data'       => null,
            'generation_error' => $this->faker->sentence(),
        ]);
    }

    public function withPdf(): static
    {
        $filename = 'report_' . $this->faker->uuid() . '.pdf';
        return $this->state([
            'pdf_path' => 'pdfs/reports/' . $filename,
            'pdf_url'  => null,
        ]);
    }

    // -----------------------------------------------------------------------
    // Private helpers
    // -----------------------------------------------------------------------

    private function fakeAuditData(int $overallScore): array
    {
        $checks = array_map(function (string $name) {
            $score  = $this->faker->numberBetween(0, 10);
            $status = match (true) {
                $score >= 7 => 'pass',
                $score >= 4 => 'warn',
                default     => 'fail',
            };

            return [
                'name'   => $name,
                'score'  => $score,
                'status' => $status,
                'note'   => $this->faker->sentence(8),
            ];
        }, self::$healthCheckNames);

        return [
            'checks'          => $checks,
            'overall_score'   => $overallScore,
            'summary'         => $this->faker->paragraph(3),
            'strengths'       => $this->faker->sentences(3),
            'weaknesses'      => $this->faker->sentences(3),
            'opportunities'   => $this->faker->sentences(2),
            'recommendations' => array_map(
                fn () => [
                    'title'       => $this->faker->bs(),
                    'description' => $this->faker->sentence(10),
                    'priority'    => $this->faker->randomElement(['high', 'medium', 'low']),
                ],
                range(1, 5)
            ),
        ];
    }
}
