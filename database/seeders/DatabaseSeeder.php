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
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Jordan Davis',
            'email' => 'superadmin@example.com',
            'role' => 'admin',
            'approval_status' => 'approved',
            'approved_at' => now(),
            'password' => 'password',
        ]);
    }
}
