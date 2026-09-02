<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Super Admin',
            'email' => 'superadmin@example.com',
            'role' => 'admin',
            'approval_status' => 'approved',
            'approved_at' => now(),
            'password' => 'password',
        ]);

        User::factory()->create([
            'name' => 'Leader One',
            'email' => 'leader@example.com',
            'role' => 'leader',
            'approval_status' => 'approved',
            'approved_at' => now(),
            'password' => 'password',
        ]);
    }
}
