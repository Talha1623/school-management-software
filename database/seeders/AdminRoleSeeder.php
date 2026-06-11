<?php

namespace Database\Seeders;


use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class AdminRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $email = 'admin@educationmanagementsystem.com';
        $plainPassword = 'Admin@12345';

        // Use DB::table to avoid model mutator double-hash scenarios.
        DB::table('admin_roles')->updateOrInsert(
            ['email' => $email],
            [
                'name' => 'Main Super Admin',
                'phone' => null,
                'password' => Hash::make($plainPassword),
                'admin_of' => null,
                'super_admin' => true,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        $this->command->info('Super Admin created/updated successfully!');
        $this->command->info("Email: {$email}");
        $this->command->info("Password: {$plainPassword}");
    }
}
