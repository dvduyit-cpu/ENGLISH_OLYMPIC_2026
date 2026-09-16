<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('speaking_criteria', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->decimal('max_score', 8, 2)->default(10);
            $table->decimal('weight', 8, 4)->default(1);
            $table->timestamps();
        });

        Schema::create('speaking_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('candidate_id')->constrained()->cascadeOnDelete();
            $table->string('room')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->enum('status', ['waiting', 'scoring', 'finished'])->default('waiting');
            $table->timestamps();
            $table->unique(['exam_event_id', 'candidate_id']);
        });

        Schema::create('speaking_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('speaking_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('judge_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('criterion_id')->constrained('speaking_criteria')->cascadeOnDelete();
            $table->decimal('score', 8, 2);
            $table->text('comment')->nullable();
            $table->timestamps();
            $table->unique(['speaking_session_id', 'judge_id', 'criterion_id'], 'speaking_score_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('speaking_scores');
        Schema::dropIfExists('speaking_sessions');
        Schema::dropIfExists('speaking_criteria');
    }
};
