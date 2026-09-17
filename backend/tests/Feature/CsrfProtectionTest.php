<?php

namespace Tests\Feature;

use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Tests\TestCase;

class CsrfProtectionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->app->bind(PreventRequestForgery::class, function ($app) {
            return new class($app, $app['encrypter']) extends PreventRequestForgery
            {
                protected function runningUnitTests(): bool
                {
                    return false;
                }
            };
        });
    }

    public function test_login_without_csrf_token_is_rejected_before_authentication(): void
    {
        $this->postJson('/api/v1/login', ['email' => 'test@example.test', 'password' => 'wrong'])->assertStatus(419);
    }

    public function test_logout_requires_csrf_token(): void
    {
        $this->postJson('/api/v1/logout')->assertStatus(419);
    }

    public function test_valid_session_csrf_token_allows_logout(): void
    {
        $this->withSession(['_token' => 'test-token'])->postJson('/api/v1/logout', [], ['X-CSRF-TOKEN' => 'test-token'])->assertNoContent();
    }
}
