<?php

namespace Database\Seeders;

use App\Enums\UserRole;
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
            'name' => 'Portal Admin',
            'email' => 'admin@example.com',
            'role' => UserRole::Admin,
        ]);

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'phone' => '0501234567',
            'emirates_id' => '784-1990-1234567-1',
            'nationality' => 'in',
        ]);

        $this->call([
            ServiceSeeder::class,
            DemoRequestSeeder::class,
        ]);
    }
}
