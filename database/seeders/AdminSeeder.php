<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        if (Admin::query()->exists()) {
            return;
        }

        // Default admin password set to 'password' as requested. Prefer setting ADMIN_DEFAULT_PASSWORD in env for production.
        $defaultPassword = (string) env('ADMIN_DEFAULT_PASSWORD', 'password');

        Admin::query()->create([
            'username' => 'admin',
            'email' => 'admin@icsa.us',
            'password_hash' => Hash::make($defaultPassword),
            'role' => Admin::ROLE_ADMIN,
            'login_attempts' => 0,
        ]);
    }
}
