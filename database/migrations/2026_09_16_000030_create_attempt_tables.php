<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('exam_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidate_id')->constrained()->cascadeOnDelete();
            $table->foreignId('round_id')->constrained()->cascadeOnDelete();
            $table->timestamp('started_at');
            $table->timestamp('submitted_at')->nullable();
            $table->unsignedBigInteger('elapsed_ms')->nullable();
            $table->decimal('score', 10, 2)->default(0);
            $table->unsignedInteger('correct_answers')->default(0);
            $table->unsignedInteger('wrong_answers')->default(0);
            $table->enum('status', ['in_progress', 'submitted', 'auto_submitted'])->default('in_progress');
            $table->unsignedInteger('rank')->nullable();
            $table->timestamps();
            $table->unique(['candidate_id', 'round_id']);
        });

        Schema::create('attempt_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attempt_id')->constrained('exam_attempts')->cascadeOnDelete();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->foreignId('option_id')->nullable()->constrained('question_options')->nullOnDelete();
            $table->boolean('is_correct')->nullable();
            $table->decimal('points_awarded', 8, 2)->default(0);
            $table->timestamp('answered_at')->nullable();
            $table->timestamps();
            $table->unique(['attempt_id', 'question_id']);
        });

        Schema::create('round_advancements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('from_round_id')->constrained('rounds')->cascadeOnDelete();
            $table->foreignId('to_round_id')->constrained('rounds')->cascadeOnDelete();
            $table->foreignId('candidate_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['to_round_id', 'candidate_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('round_advancements');
        Schema::dropIfExists('attempt_answers');
        Schema::dropIfExists('exam_attempts');
    }
};
