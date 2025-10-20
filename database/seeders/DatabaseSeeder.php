<?php
namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash; 

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create default admin (Menggunakan ENV)
        User::create([
            'name' => env('ADMIN_NAME', 'Default Admin'), 
            'email' => env('ADMIN_EMAIL', 'admin@example.com'),
            'role' => 'admin',
            'is_active' => true,
            'message_quota' => 999999,
            'email_verified_at' => now(),
            // 'password' => Hash::make(env('ADMIN_PASSWORD', 'password')) 
        ]);

        User::create([
            'name' => 'Demo User',
            'email' => 'demo.user@mail.test', 
            'role' => 'user',
            'is_active' => true,
            'message_quota' => 10000,
            'email_verified_at' => now(),
        ]);

        $this->call(SettingsSeeder::class);

        $this->command->info('✅ Default users created successfully!');
        $this->command->info('Admin: ' . env('ADMIN_EMAIL', 'admin@example.com'));
        $this->command->info('User: demo.user@mail.test');
    }
}