<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        // Create admin user
        User::create([
            'name' => 'Admin User',
            'username' => 'admin',
            'email' => 'admin@kb.com',
            'password' => Hash::make('12345678'),
            'email_verified_at' => now(),
            'is_admin' => true,
            'is_verified' => true,
        ]);

        echo "Admin user created successfully!\n";
        echo "Email: admin@kb.com\n";
        echo "Password: 12345678\n";
    }
}