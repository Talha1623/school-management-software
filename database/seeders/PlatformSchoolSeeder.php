<?php

namespace Database\Seeders;

use App\Models\AdminRole;
use App\Models\PlatformSchool;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class PlatformSchoolSeeder extends Seeder
{
    public function run(): void
    {
        $baseDomain = preg_replace('/^www\./', '', (string) parse_url(config('app.url'), PHP_URL_HOST));
        $baseDomain = $baseDomain ?: 'localhost';

        $dbHost = env('DB_HOST', '127.0.0.1');
        $dbPort = (string) env('DB_PORT', '3306');
        $dbUsername = env('DB_USERNAME', 'root');
        $dbPassword = (string) env('DB_PASSWORD', '');

        $schools = [
            [
                'name' => 'Talha School',
                'subdomain' => 'talha',
                'db_database' => 'school_talha',
                'owner_name' => 'Talha Admin',
                'owner_email' => 'talha.admin@school.com',
                'owner_plain_password' => 'Admin@123',
                'status' => 'active',
            ],
            [
                'name' => 'Talha Khan School',
                'subdomain' => 'talhakhan',
                'db_database' => 'school_talhakhan',
                'owner_name' => 'Talha Khan Admin',
                'owner_email' => 'talhakhan.admin@school.com',
                'owner_plain_password' => 'Admin@123',
                'status' => 'active',
            ],
        ];

        foreach ($schools as $item) {
            $domain = $item['subdomain'] . '.' . $baseDomain;

            $school = PlatformSchool::on('landlord')->updateOrCreate(
                ['subdomain' => $item['subdomain']],
                [
                    'name' => $item['name'],
                    'domain' => $domain,
                    'db_host' => $dbHost,
                    'db_port' => $dbPort,
                    'db_database' => $item['db_database'],
                    'db_username' => $dbUsername,
                    'db_password' => $dbPassword,
                    'owner_name' => $item['owner_name'],
                    'owner_email' => $item['owner_email'],
                    'owner_password' => Hash::make($item['owner_plain_password']),
                    'status' => $item['status'],
                ]
            );

            $tenantDatabase = str_replace('`', '``', $item['db_database']);
            $sourceDatabase = str_replace('`', '``', (string) env('DB_DATABASE', 'school'));

            // Ensure a clean tenant database every seed run.
            DB::connection('landlord')->statement("DROP DATABASE IF EXISTS `{$tenantDatabase}`");
            DB::connection('landlord')->statement("CREATE DATABASE `{$tenantDatabase}`");

            $tables = DB::connection('landlord')
                ->select("SELECT table_name FROM information_schema.tables WHERE table_schema = ? AND table_type = 'BASE TABLE'", [$sourceDatabase]);

            foreach ($tables as $tableInfo) {
                $table = str_replace('`', '``', $tableInfo->table_name);
                try {
                    DB::connection('landlord')->statement("CREATE TABLE `{$tenantDatabase}`.`{$table}` LIKE `{$sourceDatabase}`.`{$table}`");
                } catch (\Throwable $e) {
                    // Skip broken source tables during schema clone.
                    continue;
                }
            }

            config(['database.connections.tenant' => [
                'driver' => 'mysql',
                'host' => $dbHost,
                'port' => $dbPort,
                'database' => $item['db_database'],
                'username' => $dbUsername,
                'password' => $dbPassword,
                'unix_socket' => env('DB_SOCKET', ''),
                'charset' => env('DB_CHARSET', 'utf8mb4'),
                'collation' => env('DB_COLLATION', 'utf8mb4_unicode_ci'),
                'prefix' => '',
                'prefix_indexes' => true,
                'strict' => true,
                'engine' => null,
                'options' => extension_loaded('pdo_mysql') ? array_filter([
                    \PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
                ]) : [],
            ]]);
            DB::purge('tenant');

            AdminRole::on('tenant')->updateOrCreate(
                ['email' => $item['owner_email']],
                [
                    'name' => $item['owner_name'],
                    'password' => $item['owner_plain_password'],
                    'admin_of' => $school->name,
                    'super_admin' => true,
                ]
            );
        }
    }
}
