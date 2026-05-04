<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\EmailLog;
use App\Models\Report;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmailLogFactory extends Factory
{
    protected $model = EmailLog::class;

    public function definition(): array
    {
        $business = Business::factory()->withEmail()->audited()->create();
        $now      = now();

        return [
            'business_id'        => $business->id,
            'report_id'          => Report::factory()->withPdf()->create(['business_id' => $business->id])->id,
            'subject'            => 'Free Digital Audit for ' . $business->name . ' — See Your Score',
            'body_html'          => '<p>' . $this->faker->paragraphs(3, true) . '</p>',
            'body_plain'         => $this->faker->paragraphs(3, true),
            'recipient_email'    => $business->email,
            'recipient_name'     => $business->name,
            'status'             => 'pending',
            'failure_reason'     => null,
            'attempts'           => 0,
            'opened'             => false,
            'clicked'            => false,
            'opened_at'          => null,
            'clicked_at'         => null,
            'provider_message_id'=> null,
            'week_number'        => (int) $now->format('W'),
            'week_year'          => (int) $now->year,
            'sent_at'            => null,
        ];
    }

    // -----------------------------------------------------------------------
    // States
    // -----------------------------------------------------------------------

    public function sent(): static
    {
        return $this->state(fn () => [
            'status'              => 'sent',
            'provider_message_id' => 're_' . $this->faker->uuid(),
            'sent_at'             => now()->subHours($this->faker->numberBetween(1, 72)),
            'attempts'            => 1,
        ]);
    }

    public function opened(): static
    {
        return $this->sent()->state(fn () => [
            'opened'    => true,
            'opened_at' => now()->subHours($this->faker->numberBetween(1, 48)),
        ]);
    }

    public function clicked(): static
    {
        return $this->opened()->state(fn () => [
            'clicked'    => true,
            'clicked_at' => now()->subHours($this->faker->numberBetween(1, 24)),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn () => [
            'status'         => 'failed',
            'attempts'       => $this->faker->numberBetween(1, 3),
            'failure_reason' => $this->faker->randomElement([
                'Invalid recipient address',
                'SMTP connection timeout',
                'Mailbox full',
                'Domain not found',
            ]),
        ]);
    }
}
