<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_logs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('business_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('report_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            // Generated content
            $table->string('subject');
            $table->longText('body_html');
            $table->text('body_plain')->nullable();

            // Recipient
            $table->string('recipient_email');
            $table->string('recipient_name')->nullable();

            // Delivery state
            $table->enum('status', ['pending', 'queued', 'sent', 'failed', 'bounced'])
                ->default('pending')
                ->index();

            $table->text('failure_reason')->nullable();
            $table->unsignedTinyInteger('attempts')->default(0);

            // Tracking (populated via webhook or tracking pixel)
            $table->boolean('opened')->default(false);
            $table->boolean('clicked')->default(false);
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('clicked_at')->nullable();

            // External mail provider message ID (e.g. Resend message id)
            $table->string('provider_message_id')->nullable()->index();

            // Week slot — which weekly batch this belongs to
            $table->unsignedSmallInteger('week_number')->nullable(); // ISO week
            $table->unsignedSmallInteger('week_year')->nullable();

            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['week_year', 'week_number']); // for weekly limit query
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_logs');
    }
};
