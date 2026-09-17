<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->string('answer_mode', 20)->default('choice')->after('question_type');
            $table->json('accepted_answers')->nullable()->after('question_text');
        });
        Schema::table('attempt_answers', function (Blueprint $table) {
            $table->text('text_answer')->nullable()->after('option_id');
        });
    }

    public function down(): void
    {
        Schema::table('attempt_answers', fn (Blueprint $table) => $table->dropColumn('text_answer'));
        Schema::table('questions', function (Blueprint $table) {
            $table->dropColumn(['answer_mode', 'accepted_answers']);
        });
    }
};