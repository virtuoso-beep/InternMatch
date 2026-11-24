<?php

namespace Tests\Feature;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Production runs behind the Nginx TLS proxy (docker-compose.production.yml sets TRUSTED_PROXIES and FORCE_HTTPS).
 * Laravel must then see HTTPS and the real client IP (sign-in rate limits are per IP), and generate https:// links.
 */
class ProductionProxyTest extends TestCase
{
    private const VARIABLES = ['TRUSTED_PROXIES' => '*', 'FORCE_HTTPS' => 'true'];

    protected function setUp(): void
    {
        if ($this->name() === 'test_behind_the_proxy_laravel_sees_https_and_the_client_ip') {
            foreach (self::VARIABLES as $name => $value) {
                putenv("{$name}={$value}");
                $_ENV[$name] = $_SERVER[$name] = $value;
            }
        }
        parent::setUp();
        Route::get('/_proxy-check', fn (Request $request) => ['secure' => $request->secure(), 'ip' => $request->ip(), 'url' => url('/manage')]);
    }

    protected function tearDown(): void
    {
        foreach (array_keys(self::VARIABLES) as $name) {
            putenv($name);
            unset($_ENV[$name], $_SERVER[$name]);
        }
        parent::tearDown();
    }

    public function test_behind_the_proxy_laravel_sees_https_and_the_client_ip(): void
    {
        $this->getJson('/_proxy-check', ['X-Forwarded-Proto' => 'https', 'X-Forwarded-For' => '203.0.113.9'])
            ->assertOk()->assertJson(['secure' => true, 'ip' => '203.0.113.9'])
            ->assertJsonPath('url', fn (string $url) => str_starts_with($url, 'https://'));
    }

    public function test_without_the_settings_forwarded_headers_are_ignored(): void
    {
        $this->getJson('/_proxy-check', ['X-Forwarded-Proto' => 'https', 'X-Forwarded-For' => '203.0.113.9'])
            ->assertOk()->assertJson(['secure' => false, 'ip' => '127.0.0.1'])
            ->assertJsonPath('url', fn (string $url) => str_starts_with($url, 'http://'));
    }
}
