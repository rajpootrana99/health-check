<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('businesses', function (Blueprint $table) {
            $table->id();

            // Core identity
            $table->string('name');
            $table->string('business_type')->index();
            $table->string('location')->index();

            // Contact
            $table->string('email')->nullable()->index();
            $table->string('phone')->nullable();
            $table->string('website')->nullable();

            // Address
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('country')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            // Google Places metadata
            $table->string('google_place_id')->nullable()->unique();
            $table->decimal('google_rating', 3, 1)->nullable();
            $table->unsignedInteger('google_reviews_count')->default(0);

            // Social media
            $table->string('facebook_url')->nullable();
            $table->string('instagram_url')->nullable();
            $table->string('linkedin_url')->nullable();
            $table->string('twitter_url')->nullable();

            // Scraped data (raw JSON blob)
            $table->json('scraped_data')->nullable();

            // Processing state machine
            // pending → fetched → scraping → scraped → auditing → audited → emailed | failed
            $table->enum('status', [
                'pending',
                'fetched',
                'scraping',
                'scraped',
                'auditing',
                'audited',
                'emailed',
                'failed',
            ])->default('pending')->index();

            $table->text('status_message')->nullable(); // last error or note

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('businesses');
    }
};
