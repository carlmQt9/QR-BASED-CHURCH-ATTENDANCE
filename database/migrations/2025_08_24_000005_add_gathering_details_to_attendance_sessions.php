<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_sessions', function (Blueprint $table) {
            $table->string('type')->default('Sunday worship')->after('name');
            $table->unsignedSmallInteger('duration_minutes')->default(90)->after('ended_at');
        });
    }

    public function down(): void
    {
        Schema::table('attendance_sessions', function (Blueprint $table) {
            $table->dropColumn(['type', 'duration_minutes']);
        });
    }
};