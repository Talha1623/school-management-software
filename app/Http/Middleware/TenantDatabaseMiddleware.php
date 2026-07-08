<?php

namespace App\Http\Middleware;

use App\Models\PlatformSchool;
use App\Services\TenantDatabaseService;
use Closure;
use Illuminate\Http\Request;
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

        try {
            app(TenantDatabaseService::class)->connect($school);
        } catch (\Throwable $exception) {
            abort(503, $exception->getMessage());
        }

        DB::setDefaultConnection('tenant');

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
