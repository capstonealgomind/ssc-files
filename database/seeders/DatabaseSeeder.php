<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Admin account — can manage elections and create other admins
        User::firstOrCreate(
            ['email' => 'admin@sscevs.admin.com'],
            [
                'name'     => 'System Administrator',
                'password' => Hash::make('Admin@1234'),
                'role'     => 'admin',
            ]
        );

        // Sample voter account — normal email = voter role
        User::firstOrCreate(
            ['email' => 'juan@example.com'],
            [
                'name'     => 'Juan dela Cruz',
                'password' => Hash::make('Voter@1234'),
                'role'     => 'voter',
            ]
        );
    }
}
