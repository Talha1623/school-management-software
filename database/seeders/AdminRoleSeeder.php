<?php

namespace Database\Seeders;

use App\Models\AdminRole;
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
        // Check if super admin already exists
        $existingAdmin = AdminRole::where('email', 'admin@school.com')->first();
        
        if (!$existingAdmin) {
            // Use DB::table to bypass model's setPasswordAttribute (which hashes again)
            DB::table('admin_roles')->insert([
                'name' => 'Super Admin',
                'email' => 'admin@school.com',
                'password' => Hash::make('admin123'),
                'phone' => null,
                'admin_of' => null,
                'super_admin' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            $this->command->info('Super Admin created successfully!');
            $this->command->info('Email: admin@school.com');
            $this->command->info('Password: admin123');
        } else {
            $this->command->info('Super Admin already exists!');
            $this->command->info('Updating password...');
            
            // Update password directly in database
            DB::table('admin_roles')
                ->where('email', 'admin@school.com')
                ->update([
                    'password' => Hash::make('admin123'),
                    'updated_at' => now(),
                ]);
            
            $this->command->info('Password updated!');
            $this->command->info('Email: admin@school.com');
            $this->command->info('Password: admin123');
        }
    }
}
