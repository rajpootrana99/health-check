<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_logs', function (Blueprint $table) {
            $table->id();

            // Which entity this job operated on (polymorphic-style, no FK constraint
            // so logs survive after business/report deletion)
            $table->string('entity_type')->nullable();   // e.g. "business", "report"
            $table->unsignedBigInteger('entity_id')->nullable();

            $table->string('job_class');           // e.g. App\Jobs\ScrapeBusinessJob
            $table->string('queue')->default('default');

            $table->enum('status', ['dispatched', 'processing', 'completed', 'failed', 'retrying'])
                ->default('dispatched')
                ->index();

            $table->unsignedTinyInteger('attempt')->default(1);
            $table->text('payload')->nullable();    // JSON summary of job data
            $table->text('output')->nullable();     // success notes
            $table->text('error')->nullable();      // exception message + trace snippet

            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->unsignedInteger('duration_ms')->nullable(); // wall-clock ms

            $table->timestamps();

            $table->index(['entity_type', 'entity_id']);
            $table->index('job_class');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_logs');
    }
};
