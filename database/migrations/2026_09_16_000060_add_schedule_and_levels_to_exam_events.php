<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('exam_events', function (Blueprint $table) {
            $table->dateTime('starts_at')->nullable();
            $table->dateTime('ends_at')->nullable();
            $table->boolean('allow_ket')->default(true);
            $table->boolean('allow_pet')->default(true);
        });
    }
    public function down(): void
    {
        Schema::table('exam_events', function (Blueprint $table) {
            $table->dropColumn(['starts_at','ends_at','allow_ket','allow_pet']);
        });
    }
};