<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\JobLog;
use Illuminate\Database\Eloquent\Factories\Factory;

class JobLogFactory extends Factory
{
    protected $model = JobLog::class;

    private static array $jobClasses = [
        'App\\Jobs\\FetchBusinessesJob',
        'App\\Jobs\\ScrapeBusinessJob',
        'App\\Jobs\\GenerateReportJob',
        'App\\Jobs\\SendEmailJob',
    ];

    public function definition(): array
    {
        $startedAt  = now()->subMinutes($this->faker->numberBetween(1, 60));
        $finishedAt = (clone $startedAt)->addSeconds($this->faker->numberBetween(2, 120));

        return [
            'entity_type' => 'business',
            'entity_id'   => Business::factory(),
            'job_class'   => $this->faker->randomElement(self::$jobClasses),
            'queue'       => $this->faker->randomElement(['default', 'scraping', 'ai', 'email']),
            'status'      => 'completed',
            'attempt'     => 1,
            'payload'     => json_encode(['business_id' => $this->faker->numberBetween(1, 100)]),
            'output'      => $this->faker->sentence(),
            'error'       => null,
            'started_at'  => $startedAt,
            'finished_at' => $finishedAt,
            'duration_ms' => $startedAt->diffInMilliseconds($finishedAt),
        ];
    }

    // -----------------------------------------------------------------------
    // States
    // -----------------------------------------------------------------------

    public function failed(): static
    {
        return $this->state(fn () => [
            'status' => 'failed',
            'error'  => $this->faker->randomElement([
                'cURL error 28: Operation timed out',
                'Illuminate\\Http\\Client\\ConnectionException: Connection refused',
                'Anthropic API rate limit exceeded',
                'Google Places API quota exceeded',
            ]),
            'output' => null,
        ]);
    }

    public function processing(): static
    {
        return $this->state([
            'status'      => 'processing',
            'finished_at' => null,
            'duration_ms' => null,
        ]);
    }
}
