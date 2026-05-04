<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\Campaign;
use Illuminate\Database\Eloquent\Factories\Factory;

class BusinessFactory extends Factory
{
    protected $model = Business::class;

    private static array $statuses = [
        'pending', 'fetched', 'scraping', 'scraped',
        'auditing', 'audited', 'emailed', 'failed',
    ];

    public function definition(): array
    {
        $hasWebsite = $this->faker->boolean(80);
        $hasEmail   = $this->faker->boolean(65);

        return [
            'campaign_id'          => Campaign::factory(),
            'name'                 => $this->faker->company(),
            'business_type'        => $this->faker->randomElement(['Dentist', 'Plumber', 'Restaurant', 'Gym', 'Salon']),
            'location'             => $this->faker->city() . ', ' . $this->faker->country(),
            'email'                => $hasEmail ? $this->faker->companyEmail() : null,
            'phone'                => $this->faker->phoneNumber(),
            'website'              => $hasWebsite ? 'https://www.' . $this->faker->domainName() : null,
            'address'              => $this->faker->streetAddress(),
            'city'                 => $this->faker->city(),
            'country'              => $this->faker->country(),
            'latitude'             => $this->faker->latitude(),
            'longitude'            => $this->faker->longitude(),
            'google_place_id'      => 'ChIJ' . $this->faker->lexify(str_repeat('?', 27)),
            'google_rating'        => $this->faker->randomFloat(1, 2.5, 5.0),
            'google_reviews_count' => $this->faker->numberBetween(5, 800),
            'facebook_url'         => $this->faker->optional(0.6)->url(),
            'instagram_url'        => $this->faker->optional(0.5)->url(),
            'linkedin_url'         => $this->faker->optional(0.3)->url(),
            'twitter_url'          => $this->faker->optional(0.2)->url(),
            'scraped_data'         => null,
            'status'               => 'pending',
            'status_message'       => null,
        ];
    }

    // -----------------------------------------------------------------------
    // States
    // -----------------------------------------------------------------------

    public function scraped(): static
    {
        return $this->state(fn () => [
            'status'       => 'scraped',
            'scraped_data' => $this->fakeScrapeData(),
        ]);
    }

    public function audited(): static
    {
        return $this->state(['status' => 'audited']);
    }

    public function emailed(): static
    {
        return $this->state(['status' => 'emailed']);
    }

    public function failed(): static
    {
        return $this->state([
            'status'         => 'failed',
            'status_message' => $this->faker->sentence(),
        ]);
    }

    public function withEmail(): static
    {
        return $this->state(['email' => $this->faker->companyEmail()]);
    }

    public function withoutEmail(): static
    {
        return $this->state(['email' => null]);
    }

    // -----------------------------------------------------------------------
    // Private helpers
    // -----------------------------------------------------------------------

    private function fakeScrapeData(): array
    {
        return [
            'title'            => $this->faker->company() . ' | ' . $this->faker->bs(),
            'meta_description' => $this->faker->sentence(12),
            'h1'               => $this->faker->bs(),
            'h2s'              => $this->faker->sentences(3),
            'word_count'       => $this->faker->numberBetween(200, 3000),
            'has_contact_form' => $this->faker->boolean(60),
            'has_phone'        => $this->faker->boolean(80),
            'has_ssl'          => $this->faker->boolean(90),
            'page_speed_score' => $this->faker->numberBetween(20, 100),
            'mobile_score'     => $this->faker->numberBetween(30, 100),
            'images_count'     => $this->faker->numberBetween(1, 40),
            'internal_links'   => $this->faker->numberBetween(5, 80),
            'external_links'   => $this->faker->numberBetween(0, 20),
            'scraped_at'       => now()->toISOString(),
        ];
    }
}
