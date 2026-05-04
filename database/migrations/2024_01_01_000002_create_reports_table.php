<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->id();

            $table->foreignId('business_id')
                ->constrained()
                ->cascadeOnDelete();

            // ---------------------------------------------------------------
            // AI Audit Results (structured JSON)
            // ---------------------------------------------------------------
            // audit_data stores the raw Claude JSON response:
            // {
            //   "checks": [ { "name": "...", "score": 0-10, "status": "pass|warn|fail", "note": "..." }, ... ],
            //   "overall_score": 0-100,
            //   "summary": "...",
            //   "strengths": [...],
            //   "weaknesses": [...],
            //   "opportunities": [...],
            //   "recommendations": [...]
            // }
            $table->json('audit_data')->nullable();

            // Denormalized for quick querying
            $table->unsignedTinyInteger('overall_score')->nullable(); // 0–100
            $table->text('summary')->nullable();

            // PDF
            $table->string('pdf_path')->nullable();   // storage-relative path
            $table->string('pdf_url')->nullable();     // public URL (if using S3/CDN)

            // Generation metadata
            $table->enum('status', ['pending', 'generating', 'ready', 'failed'])
                ->default('pending')
                ->index();

            $table->string('ai_model_used')->nullable();       // e.g. claude-opus-4-6
            $table->unsignedInteger('ai_tokens_used')->nullable();
            $table->text('generation_error')->nullable();

            $table->timestamp('generated_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
