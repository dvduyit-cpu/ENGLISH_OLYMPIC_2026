<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('exam_events', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->date('exam_date')->nullable();
            $table->enum('status', ['draft', 'active', 'finished'])->default('draft');
            $table->timestamps();
        });

        Schema::create('levels', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('candidates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('level_id')->constrained()->restrictOnDelete();
            $table->string('candidate_code', 50);
            $table->string('full_name');
            $table->string('class_name')->nullable();
            $table->string('computer_no')->nullable();
            $table->string('pin_hash');
            $table->timestamp('checkin_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->string('last_ip', 45)->nullable();
            $table->enum('status', ['active', 'locked', 'finished'])->default('active');
            $table->timestamps();
            $table->unique(['exam_event_id', 'candidate_code']);
        });

        Schema::create('rounds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_event_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->unsignedInteger('round_order');
            $table->unsignedInteger('number_questions');
            $table->unsignedInteger('time_limit_seconds');
            $table->enum('status', ['waiting', 'open', 'closed'])->default('waiting');
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
            $table->unique(['exam_event_id', 'round_order']);
        });

        Schema::create('round_quotas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('round_id')->constrained()->cascadeOnDelete();
            $table->foreignId('level_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('advance_count')->default(0);
            $table->timestamps();
            $table->unique(['round_id', 'level_id']);
        });

        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
        Schema::dropIfExists('round_quotas');
        Schema::dropIfExists('rounds');
        Schema::dropIfExists('candidates');
        Schema::dropIfExists('levels');
        Schema::dropIfExists('exam_events');
    }
};
