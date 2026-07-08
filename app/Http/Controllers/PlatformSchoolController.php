<?php

namespace App\Http\Controllers;

use App\Models\AdminRole;
use App\Models\PlatformSchool;
use App\Services\CpanelProvisioningService;
use App\Services\TenantDatabaseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class PlatformSchoolController extends Controller
{
    public function setupGuide(): View
    {
        $setup = [
            'auto_provision' => (bool) config('platform.auto_provision'),
            'base_domain' => (string) config('platform.base_domain'),
            'cpanel_base_url' => (string) config('services.cpanel.base_url'),
            'cpanel_username' => (string) config('services.cpanel.username'),
            'has_cpanel_token' => !empty(config('services.cpanel.token')),
        ];

        $envTemplate = implode(PHP_EOL, [
            'PLATFORM_AUTO_PROVISION=true',
            'PLATFORM_BASE_DOMAIN=educationmanagementsystem.com',
            'PLATFORM_CENTRAL_DOMAINS=educationmanagementsystem.com,www.educationmanagementsystem.com',
            '',
            'CPANEL_API_BASE_URL=https://YOUR_SERVER_HOSTNAME:2083',
            'CPANEL_USERNAME=your_cpanel_username',
            'CPANEL_API_TOKEN=your_cpanel_api_token',
            'CPANEL_SUBDOMAIN_DOCUMENT_ROOT=public_html',
        ]);

        return view('platform-super-admin.setup-guide', compact('setup', 'envTemplate'));
    }

    public function index(): View
    {
        $schools = PlatformSchool::latest()->paginate(10);
        $totalSchools = PlatformSchool::count();
        $activeSchools = PlatformSchool::where('status', 'active')->count();
        $inactiveSchools = PlatformSchool::where('status', 'inactive')->count();
        $limitedSchools = PlatformSchool::whereNotNull('student_limit')->count();
        $totalStudentCapacity = PlatformSchool::whereNotNull('student_limit')->sum('student_limit');

        return view('platform-super-admin.schools.index', compact(
            'schools',
            'totalSchools',
            'activeSchools',
            'inactiveSchools',
            'limitedSchools',
            'totalStudentCapacity'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'subdomain' => ['required', 'string', 'max:100', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', 'unique:platform_schools,subdomain'],
            'owner_name' => ['required', 'string', 'max:255'],
            'owner_email' => ['required', 'email', 'max:255', 'unique:admin_roles,email'],
            'owner_password' => ['required', 'string', 'min:8'],
            'db_host' => ['required', 'string', 'max:255'],
            'db_port' => ['required', 'numeric'],
            'db_database' => ['required', 'string', 'max:255', 'unique:platform_schools,db_database'],
            'db_username' => ['required', 'string', 'max:255'],
            'db_password' => ['nullable', 'string', 'max:255'],
            'student_limit' => ['nullable', 'integer', 'min:1'],
            'status' => ['required', 'in:active,inactive'],
        ]);

        $autoProvisionEnabled = (bool) config('platform.auto_provision');
        if ($autoProvisionEnabled && empty($validated['db_password'])) {
            return back()
                ->withInput()
                ->withErrors([
                    'db_password' => 'Database password is required when auto provisioning is enabled.',
                ]);
        }

        if ($autoProvisionEnabled) {
            $validated['db_database'] = $this->withCpanelPrefix($validated['db_database']);
            $validated['db_username'] = $this->withCpanelPrefix($validated['db_username']);

            try {
                $this->assertTenantCredentialsAreIsolated($validated['db_database'], $validated['db_username']);
            } catch (\InvalidArgumentException $exception) {
                return back()
                    ->withInput()
                    ->withErrors(['db_username' => $exception->getMessage()]);
            }
        }

        $baseDomain = (string) config('platform.base_domain');
        if ($baseDomain === '') {
            $baseDomain = preg_replace('/^www\./', '', (string) parse_url(config('app.url'), PHP_URL_HOST));
        }
        $baseDomain = $baseDomain ?: 'school.com';

        $school = DB::connection('landlord')->transaction(function () use ($validated, $baseDomain) {
            return PlatformSchool::on('landlord')->create([
                'name' => $validated['name'],
                'subdomain' => strtolower($validated['subdomain']),
                'domain' => strtolower($validated['subdomain']) . '.' . $baseDomain,
                'db_host' => $validated['db_host'],
                'db_port' => (string) $validated['db_port'],
                'db_database' => $validated['db_database'],
                'db_username' => $validated['db_username'],
                'db_password' => $validated['db_password'] ?? null,
                'owner_name' => $validated['owner_name'],
                'owner_email' => $validated['owner_email'],
                'owner_password' => Hash::make($validated['owner_password']),
                'student_limit' => $validated['student_limit'] ?? null,
                'status' => $validated['status'],
            ]);
        });

        try {
            if ($autoProvisionEnabled) {
                app(CpanelProvisioningService::class)->provisionTenant(
                    strtolower($validated['subdomain']),
                    $validated['db_database'],
                    $validated['db_username'],
                    (string) ($validated['db_password'] ?? '')
                );
            }

            // Create tenant database and initialize schema.
            $tenantDatabase = str_replace('`', '``', $validated['db_database']);
            $sourceDatabase = str_replace('`', '``', (string) env('DB_DATABASE', 'school'));

            if (!$autoProvisionEnabled) {
                DB::connection('landlord')->statement("CREATE DATABASE IF NOT EXISTS `{$tenantDatabase}`");
                $tables = DB::connection('landlord')
                    ->select("SELECT table_name FROM information_schema.tables WHERE table_schema = ? AND table_type = 'BASE TABLE'", [$sourceDatabase]);

                foreach ($tables as $tableInfo) {
                    $table = str_replace('`', '``', $tableInfo->table_name);
                    $exists = DB::connection('landlord')->selectOne(
                        "SELECT COUNT(*) as total FROM information_schema.tables WHERE table_schema = ? AND table_name = ?",
                        [$tenantDatabase, $table]
                    );

                    if (($exists->total ?? 0) > 0) {
                        continue;
                    }

                    try {
                        DB::connection('landlord')->statement("CREATE TABLE `{$tenantDatabase}`.`{$table}` LIKE `{$sourceDatabase}`.`{$table}`");
                    } catch (\Throwable $e) {
                        // Skip problematic source tables to avoid blocking school creation.
                        continue;
                    }
                }
            } else {
                // Shared hosting users often can't clone tables across databases via landlord user.
                // Run migrations directly on tenant DB using tenant credentials.
                config(['database.connections.tenant' => [
                    'driver' => 'mysql',
                    'host' => $validated['db_host'],
                    'port' => (string) $validated['db_port'],
                    'database' => $validated['db_database'],
                    'username' => $validated['db_username'],
                    'password' => $validated['db_password'] ?? '',
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
                DB::connection('tenant')->getPdo();
                // Provisioning may be retried after a partial migration failure, leaving some tables behind.
                // Fresh migration guarantees a clean tenant schema on each retry.
                Artisan::call('migrate:fresh', [
                    '--database' => 'tenant',
                    '--force' => true,
                ]);
            }

            config(['database.connections.tenant' => [
                'driver' => 'mysql',
                'host' => $validated['db_host'],
                'port' => (string) $validated['db_port'],
                'database' => $validated['db_database'],
                'username' => $validated['db_username'],
                'password' => $validated['db_password'] ?? '',
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

            AdminRole::on('tenant')->create([
                'name' => $validated['owner_name'],
                'email' => $validated['owner_email'],
                'password' => $validated['owner_password'],
                'admin_of' => $school->name,
                'super_admin' => true,
            ]);
        } catch (\Throwable $exception) {
            $school->delete();
            report($exception);

            $autoProvisionEnabled = (bool) config('platform.auto_provision');
            $errorMessage = $autoProvisionEnabled
                ? 'Auto provisioning failed via cPanel API. Please verify CPANEL_API_BASE_URL, CPANEL_USERNAME, CPANEL_API_TOKEN and API permissions.'
                : 'Auto provisioning is currently disabled. Enable PLATFORM_AUTO_PROVISION=true or create DB/subdomain manually from hosting panel.';

            if (config('app.debug')) {
                $errorMessage .= ' Error: ' . $exception->getMessage();
            }

            return back()
                ->withInput()
                ->with('error', $errorMessage);
        }

        return redirect()->route('platform-admin.schools.index')->with('success', 'School added with dedicated database and full super admin owner login.');
    }

    public function toggleStatus(PlatformSchool $school): RedirectResponse
    {
        $school->update([
            'status' => $school->status === 'active' ? 'inactive' : 'active',
        ]);

        return redirect()->route('platform-admin.schools.index')->with('success', 'School status updated successfully.');
    }

    public function updateStudentLimit(Request $request, PlatformSchool $school): RedirectResponse
    {
        $validated = $request->validate([
            'student_limit' => ['nullable', 'integer', 'min:1'],
        ]);

        $school->update([
            'student_limit' => $validated['student_limit'] ?? null,
        ]);

        return redirect()
            ->route('platform-admin.schools.index')
            ->with('success', 'Student limit updated successfully.');
    }

    public function updateDatabaseCredentials(Request $request, PlatformSchool $school): RedirectResponse
    {
        $validated = $request->validate([
            'db_host' => ['required', 'string', 'max:255'],
            'db_port' => ['required', 'numeric'],
            'db_database' => ['required', 'string', 'max:255'],
            'db_username' => ['required', 'string', 'max:255'],
            'db_password' => ['required', 'string', 'max:255'],
        ]);

        $connectionName = 'tenant_credentials_test';

        try {
            app(TenantDatabaseService::class)->testCredentials($validated, $connectionName);
        } catch (\Throwable $exception) {
            return redirect()
                ->route('platform-admin.schools.index')
                ->with('error', 'Database connection failed for ' . $school->subdomain . '. In cPanel: add user to database with ALL PRIVILEGES, reset password, then save the same password here.');
        }

        $school->update($validated);

        return redirect()
            ->route('platform-admin.schools.index')
            ->with('success', 'Database credentials updated for ' . $school->subdomain . '.');
    }

    public function destroy(PlatformSchool $school): RedirectResponse
    {
        $tenantDatabase = str_replace('`', '``', (string) $school->db_database);
        $dropFailed = false;

        // Avoid wrapping DROP DATABASE in transaction (MySQL auto-commits DDL).
        $school->delete();

        if (!empty($tenantDatabase)) {
            try {
                DB::connection('landlord')->statement("DROP DATABASE IF EXISTS `{$tenantDatabase}`");
            } catch (\Throwable $exception) {
                $dropFailed = true;
            }
        }

        if ($dropFailed) {
            return redirect()->route('platform-admin.schools.index')->with(
                'success',
                'School deleted, but tenant database could not be dropped due to server permissions.'
            );
        }

        return redirect()->route('platform-admin.schools.index')->with('success', 'School deleted successfully.');
    }

    private function withCpanelPrefix(string $value): string
    {
        $normalized = strtolower(trim($value));
        $cpanelUsername = strtolower(trim((string) config('services.cpanel.username')));

        if ($cpanelUsername === '') {
            return $normalized;
        }

        $requiredPrefix = $cpanelUsername . '_';

        if (str_starts_with($normalized, $requiredPrefix)) {
            return $normalized;
        }

        return $requiredPrefix . ltrim($normalized, '_');
    }

    private function assertTenantCredentialsAreIsolated(string $dbDatabase, string $dbUsername): void
    {
        $landlordDatabase = strtolower(trim((string) env('DB_DATABASE', '')));
        $landlordUsername = strtolower(trim((string) env('DB_USERNAME', '')));

        if ($landlordDatabase !== '' && strtolower($dbDatabase) === $landlordDatabase) {
            throw new \InvalidArgumentException(
                'Tenant database name cannot be the same as the main application database (' . $landlordDatabase . ').'
            );
        }

        if ($landlordUsername !== '' && strtolower($dbUsername) === $landlordUsername) {
            throw new \InvalidArgumentException(
                'Tenant database user cannot be the same as the main application database user (' . $landlordUsername . ').'
            );
        }
    }
}
