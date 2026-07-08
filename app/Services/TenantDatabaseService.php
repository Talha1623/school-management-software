<?php

namespace App\Services;

use App\Models\PlatformSchool;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class TenantDatabaseService
{
    public function connectionConfig(PlatformSchool $school, ?string $host = null): array
    {
        return [
            'driver' => 'mysql',
            'host' => $host ?? $this->resolveHost((string) ($school->db_host ?: env('DB_HOST', 'localhost'))),
            'port' => (string) ($school->db_port ?: env('DB_PORT', '3306')),
            'database' => (string) $school->db_database,
            'username' => (string) $school->db_username,
            'password' => (string) ($school->db_password ?? ''),
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
        ];
    }

    public function connect(PlatformSchool $school, string $connectionName = 'tenant'): void
    {
        $primaryHost = $this->resolveHost((string) ($school->db_host ?: env('DB_HOST', 'localhost')));
        $hosts = array_values(array_unique(array_filter([
            $primaryHost,
            $primaryHost === '127.0.0.1' ? 'localhost' : null,
            $primaryHost === 'localhost' ? '127.0.0.1' : null,
        ])));

        $lastException = null;

        foreach ($hosts as $host) {
            Config::set('database.connections.' . $connectionName, $this->connectionConfig($school, $host));
            DB::purge($connectionName);

            try {
                DB::connection($connectionName)->getPdo();

                if ($host !== $primaryHost) {
                    $school->forceFill(['db_host' => $host])->save();
                }

                return;
            } catch (\Throwable $exception) {
                $lastException = $exception;
            }
        }

        throw new RuntimeException(
            'Could not connect to tenant database for ' . $school->subdomain . '. '
            . 'Verify cPanel user is added to the database with ALL PRIVILEGES and that db_password in platform_schools matches cPanel.',
            0,
            $lastException
        );
    }

    public function testCredentials(array $credentials, string $connectionName = 'tenant_credentials_test'): void
    {
        $school = new PlatformSchool([
            'db_host' => $credentials['db_host'],
            'db_port' => $credentials['db_port'],
            'db_database' => $credentials['db_database'],
            'db_username' => $credentials['db_username'],
            'db_password' => $credentials['db_password'],
        ]);

        $this->connect($school, $connectionName);
    }

    private function resolveHost(string $host): string
    {
        $normalized = strtolower(trim($host));

        return $normalized === '127.0.0.1' ? 'localhost' : $normalized;
    }
}
