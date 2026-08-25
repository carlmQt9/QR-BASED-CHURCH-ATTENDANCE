<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('started_by')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->string('location')->nullable();
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();
        });

        Schema::create('attendance_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('checked_in_at');
            $table->timestamps();
            $table->unique(['attendance_session_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_records');
        Schema::dropIfExists('attendance_sessions');
    }
};