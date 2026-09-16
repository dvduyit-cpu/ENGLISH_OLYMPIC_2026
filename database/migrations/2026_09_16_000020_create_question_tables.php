<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('level_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('question_type', ['single_choice', 'listening_single_choice'])->default('single_choice');
            $table->text('question_text');
            $table->string('audio_path')->nullable();
            $table->string('image_path')->nullable();
            $table->decimal('points', 8, 2)->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('question_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->string('option_code', 5);
            $table->text('option_text');
            $table->boolean('is_correct')->default(false);
            $table->timestamps();
            $table->unique(['question_id', 'option_code']);
        });

        Schema::create('round_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('round_id')->constrained()->cascadeOnDelete();
            $table->foreignId('level_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(1);
            $table->timestamps();
            $table->unique(['round_id', 'level_id', 'question_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('round_questions');
        Schema::dropIfExists('question_options');
        Schema::dropIfExists('questions');
    }
};
