<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class PlatformSuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = 'admin@educationmanagementsystem.com';
        $plainPassword = 'Admin@12345';

        DB::connection('landlord')->table('platform_super_admins')->updateOrInsert(
            ['email' => $email],
            [
                'name' => 'Platform Super Admin',
                'password' => Hash::make($plainPassword),
                'is_active' => true,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        $this->command->info('Platform Super Admin created/updated successfully!');
        $this->command->info("Email: {$email}");
        $this->command->info("Password: {$plainPassword}");
    }
}
