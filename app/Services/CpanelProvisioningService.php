<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class CpanelProvisioningService
{
    public function provisionTenant(string $subdomain, string $database, string $dbUser, string $dbPassword): void
    {
        $this->createDatabase($database);
        $this->createDatabaseUser($dbUser, $dbPassword);
        $this->grantAllPrivileges($database, $dbUser);
        $this->createSubdomain($subdomain);
    }

    private function createDatabase(string $database): void
    {
        $this->call('Mysql', 'create_database', ['name' => $database], true);
    }

    private function createDatabaseUser(string $dbUser, string $dbPassword): void
    {
        $this->call('Mysql', 'create_user', [
            'name' => $dbUser,
            'password' => $dbPassword,
        ], true);

        // If user already exists, create_user is ignored; force password sync for reliable login.
        $this->call('Mysql', 'set_password', [
            'user' => $dbUser,
            'password' => $dbPassword,
        ]);
    }

    private function grantAllPrivileges(string $database, string $dbUser): void
    {
        $this->call('Mysql', 'set_privileges_on_database', [
            'user' => $dbUser,
            'database' => $database,
            'privileges' => 'ALL PRIVILEGES',
        ]);
    }

    private function createSubdomain(string $subdomain): void
    {
        $baseDomain = (string) config('platform.base_domain');
        if ($baseDomain === '') {
            throw new RuntimeException('PLATFORM_BASE_DOMAIN is required for cPanel subdomain provisioning.');
        }

        $documentRoot = (string) config('services.cpanel.subdomain_document_root', 'public_html');

        $this->call('SubDomain', 'addsubdomain', [
            'domain' => $subdomain,
            'rootdomain' => $baseDomain,
            'dir' => $documentRoot,
        ], true);
    }

    private function call(string $module, string $function, array $params = [], bool $allowAlreadyExists = false): array
    {
        $baseUrl = rtrim((string) config('services.cpanel.base_url'), '/');
        $username = (string) config('services.cpanel.username');
        $token = (string) config('services.cpanel.token');

        if ($baseUrl === '' || $username === '' || $token === '') {
            throw new RuntimeException('cPanel API credentials are missing. Configure CPANEL_API_BASE_URL, CPANEL_USERNAME, and CPANEL_API_TOKEN.');
        }

        $url = $baseUrl . '/execute/' . $module . '/' . $function;

        $response = Http::withHeaders([
            'Authorization' => 'cpanel ' . $username . ':' . $token,
        ])->timeout(30)->asForm()->post($url, $params);

        if (!$response->successful()) {
            throw new RuntimeException('cPanel API request failed for ' . $module . '::' . $function . '. HTTP ' . $response->status());
        }

        $payload = $response->json();
        $status = (int) data_get($payload, 'status', 0);

        if ($status === 1) {
            return $payload;
        }

        $errors = data_get($payload, 'errors', []);
        $errorText = is_array($errors) ? implode(' | ', $errors) : (string) $errors;
        $errorText = $errorText !== '' ? $errorText : 'Unknown cPanel API error.';

        if ($allowAlreadyExists && str_contains(strtolower($errorText), 'already exists')) {
            return $payload;
        }

        throw new RuntimeException('cPanel API error in ' . $module . '::' . $function . ' - ' . $errorText);
    }
}
