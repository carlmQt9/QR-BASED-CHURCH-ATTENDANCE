<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value');
            $table->string('type')->default('array'); // array, string, json
            $table->timestamps();
        });

        // Insert default values
        DB::table('settings')->insert([
            [
                'key' => 'gathering_types',
                'value' => json_encode([
                    'Sunday worship',
                    'Prayer meeting',
                    'Saturday worship practice',
                    'Youth fellowship',
                    'Small group',
                    'Special event'
                ]),
                'type' => 'array',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'membership_groups',
                'value' => json_encode([
                    'General congregation',
                    'Volunteer team',
                    'Youth ministry'
                ]),
                'type' => 'array',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
