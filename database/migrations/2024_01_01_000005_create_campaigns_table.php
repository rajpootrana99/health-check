<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A Campaign represents one admin-triggered run:
     *   business_type = "Dentists", location = "London"
     *
     * Businesses belong to a campaign so the admin can review
     * one batch without it mixing with others.
     */
    public function up(): void
    {
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();

            $table->string('business_type');
            $table->string('location');
            $table->string('label')->nullable(); // human-readable, auto-generated if empty

            $table->enum('status', ['pending', 'fetching', 'processing', 'completed', 'failed'])
                ->default('pending')
                ->index();

            $table->unsignedSmallInteger('total_businesses')->default(0);
            $table->unsignedSmallInteger('audited_count')->default(0);
            $table->unsignedSmallInteger('emailed_count')->default(0);

            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // Add campaign_id FK to businesses
        Schema::table('businesses', function (Blueprint $table) {
            $table->foreignId('campaign_id')
                ->nullable()
                ->after('id')
                ->constrained()
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropForeign(['campaign_id']);
            $table->dropColumn('campaign_id');
        });

        Schema::dropIfExists('campaigns');
    }
};
