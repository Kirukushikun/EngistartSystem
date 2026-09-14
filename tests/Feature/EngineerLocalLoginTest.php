<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Engineers are provisioned with a real, IT-staff-set local password (see
 * AssignedEngineersPage) precisely because they have no account in BFC's
 * central Auth API. Out of testing mode, login must still authenticate them
 * locally -- never attempt the external API for them at all.
 */
class EngineerLocalLoginTest extends TestCase
{
    use RefreshDatabase;

    protected function goLive(): void
    {
        // A Turnstile secret is what takes the system out of testing mode.
        config()->set('services.turnstile.secret', 'real-secret');
        config()->set('auth.api.base_uri', 'https://auth.example.test');
        config()->set('auth.api.api_key', 'test-key');
        config()->set('auth.api.auth_user_api_key', 'test-user-key');
    }

    public function test_an_engineer_logs_in_locally_even_when_out_of_testing_mode(): void
    {
        $this->goLive();

        $engineer = User::factory()->create([
            'role' => 'engineer',
            'is_active' => true,
            'password' => Hash::make('correct-horse'),
        ]);

        // If the code takes the API path at all, this fake has nothing
        // registered and Http::fake() will throw -- proving the external
        // API was never called for this account.
        Http::fake();

        $this->post('/login', [
            'email' => $engineer->email,
            'password' => 'correct-horse',
        ])->assertRedirect(route('engineer.inbox'));

        $this->assertAuthenticatedAs($engineer);
        Http::assertNothingSent();
    }

    public function test_an_engineer_with_the_wrong_password_gets_the_local_login_error_not_an_api_error(): void
    {
        $this->goLive();

        $engineer = User::factory()->create([
            'role' => 'engineer',
            'is_active' => true,
            'password' => Hash::make('correct-horse'),
        ]);

        Http::fake();

        $this->post('/login', [
            'email' => $engineer->email,
            'password' => 'wrong-password',
        ])->assertSessionHasErrors(['email' => 'The provided credentials do not match our records.']);

        $this->assertGuest();
        Http::assertNothingSent();
    }

    public function test_a_non_engineer_still_goes_through_the_api_when_out_of_testing_mode(): void
    {
        $this->goLive();

        $farmManager = User::factory()->create(['id' => 55, 'role' => 'farm_manager', 'is_active' => true]);

        Http::fake([
            '*challenges.cloudflare.com*' => Http::response(['success' => true], 200),
            '*/api/v1/auth/login' => Http::response(['token' => 'abc', 'email' => $farmManager->email], 200),
            '*/api/v1/users/get-user-id*' => Http::response(['id' => 55], 200),
        ]);

        $this->post('/login', [
            'email' => $farmManager->email,
            'password' => 'whatever-the-api-validates',
        ])->assertRedirect(route('farm-manager.requests.new'));

        $this->assertAuthenticatedAs($farmManager);
        Http::assertSent(fn ($request) => str_contains($request->url(), '/api/v1/auth/login'));
    }

    public function test_an_engineer_still_logs_in_locally_while_testing_mode_is_also_on(): void
    {
        // Testing mode on (no Turnstile secret) -- the engineer branch and the
        // testing-mode branch should agree, not conflict.
        $engineer = User::factory()->create([
            'role' => 'engineer',
            'is_active' => true,
            'password' => Hash::make('correct-horse'),
        ]);

        $this->post('/login', [
            'email' => $engineer->email,
            'password' => 'correct-horse',
        ])->assertRedirect(route('engineer.inbox'));

        $this->assertAuthenticatedAs($engineer);
    }

    public function test_an_unknown_email_out_of_testing_mode_still_falls_through_to_the_api_path(): void
    {
        $this->goLive();

        Http::fake([
            '*challenges.cloudflare.com*' => Http::response(['success' => true], 200),
            '*/api/v1/auth/login' => Http::response(['message' => 'Incorrect username or password.'], 401),
        ]);

        $this->post('/login', [
            'email' => 'nobody@bfcgroup.org',
            'password' => 'whatever',
        ]);

        Http::assertSent(fn ($request) => str_contains($request->url(), '/api/v1/auth/login'));
    }
}
