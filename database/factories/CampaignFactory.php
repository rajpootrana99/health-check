<?php

namespace Database\Factories;

use App\Models\Campaign;
use Illuminate\Database\Eloquent\Factories\Factory;

class CampaignFactory extends Factory
{
    protected $model = Campaign::class;

    private static array $types = [
        'Dentists', 'Plumbers', 'Electricians', 'Restaurants',
        'Gyms', 'Law Firms', 'Accountants', 'Real Estate Agents',
        'Hair Salons', 'Auto Repair Shops',
    ];

    private static array $cities = [
        'London', 'Manchester', 'Birmingham', 'Leeds',
        'Dubai', 'New York', 'Sydney', 'Toronto',
    ];

    public function definition(): array
    {
        $type     = $this->faker->randomElement(self::$types);
        $location = $this->faker->randomElement(self::$cities);

        return [
            'business_type' => $type,
            'location'      => $location,
            'label'         => "{$type} in {$location}",
            'status'        => $this->faker->randomElement(['pending', 'processing', 'completed']),
            'notes'         => $this->faker->optional()->sentence(),
        ];
    }

    public function completed(): static
    {
        return $this->state(['status' => 'completed']);
    }

    public function pending(): static
    {
        return $this->state(['status' => 'pending']);
    }
}
