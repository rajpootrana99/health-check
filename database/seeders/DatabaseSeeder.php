<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Run: php artisan db:seed
     * Reset + seed: php artisan migrate:fresh --seed
     */
    public function run(): void
    {
        $this->call([
            CampaignSeeder::class,
        ]);
    }
}
