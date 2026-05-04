<?php

namespace Database\Seeders;

use App\Models\Business;
use App\Models\Campaign;
use App\Models\EmailLog;
use App\Models\Report;
use Illuminate\Database\Seeder;

class CampaignSeeder extends Seeder
{
    /**
     * Seeds a realistic dataset:
     *  - 1 completed campaign (Dentists in London) with full audit trail
     *  - 1 in-progress campaign (Plumbers in Manchester) with mixed states
     *  - 1 fresh campaign (Gyms in Dubai) with only fetched businesses
     */
    public function run(): void
    {
        // ------------------------------------------------------------------
        // Campaign 1: Completed — Dentists in London
        // ------------------------------------------------------------------
        $campaign1 = Campaign::create([
            'business_type' => 'Dentists',
            'location'      => 'London',
            'label'         => 'Dentists in London',
            'status'        => 'completed',
        ]);

        // 10 businesses — all emailed
        Business::factory()
            ->count(10)
            ->withEmail()
            ->emailed()
            ->create(['campaign_id' => $campaign1->id, 'business_type' => 'Dentist', 'location' => 'London'])
            ->each(function (Business $b) {
                $report = Report::factory()->withPdf()->create(['business_id' => $b->id]);
                EmailLog::factory()->sent()->create([
                    'business_id' => $b->id,
                    'report_id'   => $report->id,
                    'subject'     => "Free Digital Audit for {$b->name}",
                ]);
            });

        // 2 businesses without email — skipped
        Business::factory()
            ->count(2)
            ->withoutEmail()
            ->audited()
            ->create(['campaign_id' => $campaign1->id, 'business_type' => 'Dentist', 'location' => 'London']);

        $campaign1->refreshCounts();

        // ------------------------------------------------------------------
        // Campaign 2: Processing — Plumbers in Manchester
        // ------------------------------------------------------------------
        $campaign2 = Campaign::create([
            'business_type' => 'Plumbers',
            'location'      => 'Manchester',
            'label'         => 'Plumbers in Manchester',
            'status'        => 'processing',
        ]);

        // 5 fully audited, waiting for email queue
        Business::factory()
            ->count(5)
            ->withEmail()
            ->audited()
            ->create(['campaign_id' => $campaign2->id, 'business_type' => 'Plumber', 'location' => 'Manchester'])
            ->each(fn (Business $b) => Report::factory()->withPdf()->create(['business_id' => $b->id]));

        // 3 scraped, AI audit in progress
        Business::factory()
            ->count(3)
            ->scraped()
            ->create(['campaign_id' => $campaign2->id, 'business_type' => 'Plumber', 'location' => 'Manchester']);

        // 2 failed during scrape
        Business::factory()
            ->count(2)
            ->failed()
            ->create(['campaign_id' => $campaign2->id, 'business_type' => 'Plumber', 'location' => 'Manchester']);

        $campaign2->refreshCounts();

        // ------------------------------------------------------------------
        // Campaign 3: Fresh — Gyms in Dubai
        // ------------------------------------------------------------------
        $campaign3 = Campaign::create([
            'business_type' => 'Gyms',
            'location'      => 'Dubai',
            'label'         => 'Gyms in Dubai',
            'status'        => 'fetching',
        ]);

        Business::factory()
            ->count(8)
            ->create(['campaign_id' => $campaign3->id, 'business_type' => 'Gym', 'location' => 'Dubai', 'status' => 'fetched']);

        $campaign3->refreshCounts();

        $this->command->info('CampaignSeeder: 3 campaigns with ' .
            Business::count() . ' businesses seeded.');
    }
}
