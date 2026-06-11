<?php

namespace App\Http\Middleware;

use App\Models\PlatformSchool;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class TenantDatabaseMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // Route name may not be resolved yet at this middleware stage, so guard by path first.
        if ($request->is('platform-admin') || $request->is('platform-admin/*') || $request->routeIs('platform-admin.*')) {
            DB::setDefaultConnection('landlord');
            return $next($request);
        }

        $host = strtolower($request->getHost());
        $normalizedHost = $this->normalizeTenantHost($host);
        $centralHosts = $this->centralHosts();

        if (in_array($host, $centralHosts, true) || in_array($normalizedHost, $centralHosts, true)) {
            DB::setDefaultConnection('landlord');
            return $next($request);
        }

        $school = PlatformSchool::on('landlord')
            ->where('status', 'active')
            ->where(function ($query) use ($host, $normalizedHost) {
                $query->whereRaw('LOWER(domain) = ?', [$host])
                    ->orWhereRaw('LOWER(domain) = ?', [$normalizedHost])
                    ->orWhereRaw('LOWER(subdomain) = ?', [strtok($normalizedHost, '.') ?: $normalizedHost]);
            })
            ->first();

        if (!$school || !$school->db_database || !$school->db_username) {
            abort(404, 'School database is not configured for this domain.');
        }

        // Same tenant may be reached as www.demo.example.com or demo.example.com; sessions are host-only
        // unless SESSION_DOMAIN is set. Redirect to the canonical host stored on the school so admin cookies apply.
        $canonicalHost = strtolower(trim((string) $school->domain));
        if ($canonicalHost !== '' && $host !== $canonicalHost && $normalizedHost === $canonicalHost) {
            $target = $request->getScheme() . '://' . $canonicalHost . $request->getRequestUri();

            return redirect()->to($target);
        }

        Config::set('database.connections.tenant', [
            'driver' => 'mysql',
            'host' => $school->db_host ?: env('DB_HOST', '127.0.0.1'),
            'port' => $school->db_port ?: env('DB_PORT', '3306'),
            'database' => $school->db_database,
            'username' => $school->db_username,
            'password' => $school->db_password ?? '',
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
        ]);

        DB::purge('tenant');
        DB::setDefaultConnection('tenant');
        DB::connection('tenant')->getPdo();

        return $next($request);
    }

    private function centralHosts(): array
    {
        $hosts = ['localhost', '127.0.0.1'];
        $appHost = parse_url((string) config('app.url'), PHP_URL_HOST);
        if ($appHost) {
            $hosts[] = strtolower($appHost);
        }

        $baseDomain = (string) config('platform.base_domain');
        if ($baseDomain !== '') {
            $hosts[] = strtolower($baseDomain);
            $hosts[] = 'www.' . strtolower($baseDomain);
        }

        foreach ((array) config('platform.central_domains', []) as $domain) {
            if (!empty($domain)) {
                $hosts[] = strtolower((string) $domain);
            }
        }

        return array_values(array_unique($hosts));
    }

    private function normalizeTenantHost(string $host): string
    {
        $normalized = strtolower(trim($host));
        if (str_starts_with($normalized, 'www.')) {
            return substr($normalized, 4);
        }

        return $normalized;
    }
}
