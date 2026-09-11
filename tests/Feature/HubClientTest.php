<?php

namespace Tests\Feature;

use App\Models\HubConnection;
use App\Support\HubClient;
use App\Support\HubConnectionRejected;
use App\Support\HubEnrollmentException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

/**
 * Exercises the real network calls -- headers sent, status-code handling, and
 * the last_synced_at stamp -- as opposed to HubSyncTest, which binds a fake
 * HubClient and never touches HTTP.
 */
class HubClientTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['hub.base_url' => 'https://accesshub.bfcgroup.ph']);
        config(['hub.project_key' => 'ENGISTART']);
    }

    // ---- enroll -----------------------------------------------------------

    public function test_enroll_sends_the_code_project_key_and_environment(): void
    {
        Http::fake([
            '*/api/v1/enroll' => Http::response(['client_id' => 'chub_abc', 'client_secret' => 'topsecret'], 200),
        ]);

        (new HubClient())->enroll('HUB-TEST-CODE');

        Http::assertSent(function ($request) {
            return $request->url() === 'https://accesshub.bfcgroup.ph/api/v1/enroll'
                && $request->method() === 'POST'
                && $request['code'] === 'HUB-TEST-CODE'
                && $request['project_key'] === 'ENGISTART'
                && $request['environment'] === app()->environment();
        });
    }

    public function test_enroll_stores_the_returned_credentials(): void
    {
        Http::fake([
            '*/api/v1/enroll' => Http::response(['client_id' => 'chub_abc', 'client_secret' => 'topsecret'], 200),
        ]);

        $connection = (new HubClient())->enroll('HUB-TEST-CODE');

        $this->assertSame('chub_abc', $connection->client_id);
        $this->assertSame('topsecret', HubConnection::current()->client_secret);
    }

    public function test_enroll_replaces_any_prior_connection(): void
    {
        HubConnection::create(['client_id' => 'old', 'client_secret' => 'old-secret']);

        Http::fake([
            '*/api/v1/enroll' => Http::response(['client_id' => 'new', 'client_secret' => 'new-secret'], 200),
        ]);

        (new HubClient())->enroll('HUB-TEST-CODE');

        $this->assertSame(1, HubConnection::count());
        $this->assertSame('new', HubConnection::current()->client_id);
    }

    public function test_enroll_requires_a_configured_project_key(): void
    {
        config(['hub.project_key' => '']);

        $this->expectException(HubEnrollmentException::class);
        $this->expectExceptionMessage('HUB_PROJECT_KEY');

        (new HubClient())->enroll('HUB-TEST-CODE');
    }

    public function test_enroll_reports_an_unrecognized_project_key(): void
    {
        Http::fake(['*/api/v1/enroll' => Http::response([], 404)]);

        try {
            (new HubClient())->enroll('HUB-TEST-CODE');
            $this->fail('Expected HubEnrollmentException.');
        } catch (HubEnrollmentException $e) {
            $this->assertStringContainsString('project key', $e->getMessage());
        }

        $this->assertNull(HubConnection::current());
    }

    public function test_enroll_reports_a_bad_or_expired_code(): void
    {
        Http::fake(['*/api/v1/enroll' => Http::response([], 422)]);

        try {
            (new HubClient())->enroll('HUB-BAD-CODE');
            $this->fail('Expected HubEnrollmentException.');
        } catch (HubEnrollmentException $e) {
            $this->assertStringContainsString('wrong, expired, or already used', $e->getMessage());
        }
    }

    public function test_enroll_reports_rate_limiting(): void
    {
        Http::fake(['*/api/v1/enroll' => Http::response([], 429)]);

        try {
            (new HubClient())->enroll('HUB-TEST-CODE');
            $this->fail('Expected HubEnrollmentException.');
        } catch (HubEnrollmentException $e) {
            $this->assertStringContainsString('rate-limiting', $e->getMessage());
        }
    }

    // ---- fetchGrants --------------------------------------------------------

    public function test_fetch_grants_requires_a_connection(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No Access Hub connection');

        (new HubClient())->fetchGrants();
    }

    public function test_fetch_grants_sends_the_stored_credentials_as_headers(): void
    {
        HubConnection::create(['client_id' => 'chub_abc', 'client_secret' => 'topsecret']);

        Http::fake([
            '*/api/v1/grants' => Http::response(['generated_at' => now()->toIso8601String(), 'people' => []], 200),
        ]);

        (new HubClient())->fetchGrants();

        Http::assertSent(function ($request) {
            return $request->url() === 'https://accesshub.bfcgroup.ph/api/v1/grants'
                && $request->method() === 'GET'
                && $request->hasHeader('X-Client-Id', 'chub_abc')
                && $request->hasHeader('X-Client-Secret', 'topsecret');
        });
    }

    public function test_fetch_grants_returns_the_people_array(): void
    {
        HubConnection::create(['client_id' => 'chub_abc', 'client_secret' => 'topsecret']);

        Http::fake([
            '*/api/v1/grants' => Http::response([
                'generated_at' => now()->toIso8601String(),
                'people' => [['user_id' => 1, 'name' => 'A', 'email' => 'a@x.com', 'roles' => ['manager'], 'active' => true]],
            ], 200),
        ]);

        $people = (new HubClient())->fetchGrants();

        $this->assertCount(1, $people);
        $this->assertSame(1, $people[0]['user_id']);
    }

    public function test_fetch_grants_stamps_last_synced_at_on_success(): void
    {
        $connection = HubConnection::create(['client_id' => 'chub_abc', 'client_secret' => 'topsecret']);
        $this->assertNull($connection->last_synced_at);

        Http::fake([
            '*/api/v1/grants' => Http::response(['generated_at' => now()->toIso8601String(), 'people' => []], 200),
        ]);

        (new HubClient())->fetchGrants();

        $this->assertNotNull(HubConnection::current()->last_synced_at);
        $this->assertTrue(HubConnection::current()->last_synced_at->greaterThan(now()->subMinute()));
    }

    public function test_fetch_grants_throws_connection_rejected_on_401(): void
    {
        HubConnection::create(['client_id' => 'chub_abc', 'client_secret' => 'wrong']);

        Http::fake(['*/api/v1/grants' => Http::response([], 401)]);

        $this->expectException(HubConnectionRejected::class);

        (new HubClient())->fetchGrants();
    }

    public function test_fetch_grants_throws_connection_rejected_on_403(): void
    {
        HubConnection::create(['client_id' => 'chub_abc', 'client_secret' => 'revoked']);

        Http::fake(['*/api/v1/grants' => Http::response([], 403)]);

        $this->expectException(HubConnectionRejected::class);

        (new HubClient())->fetchGrants();
    }

    public function test_fetch_grants_does_not_stamp_last_synced_at_when_rejected(): void
    {
        HubConnection::create(['client_id' => 'chub_abc', 'client_secret' => 'wrong']);

        Http::fake(['*/api/v1/grants' => Http::response([], 401)]);

        try {
            (new HubClient())->fetchGrants();
        } catch (HubConnectionRejected) {
            // expected
        }

        $this->assertNull(HubConnection::current()->last_synced_at);
    }

    public function test_fetch_grants_throws_on_a_malformed_response(): void
    {
        HubConnection::create(['client_id' => 'chub_abc', 'client_secret' => 'topsecret']);

        Http::fake(['*/api/v1/grants' => Http::response(['not_people' => []], 200)]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('malformed');

        (new HubClient())->fetchGrants();
    }

    public function test_fetch_grants_requires_a_configured_base_url(): void
    {
        HubConnection::create(['client_id' => 'chub_abc', 'client_secret' => 'topsecret']);
        config(['hub.base_url' => '']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('HUB_BASE_URL');

        (new HubClient())->fetchGrants();
    }
}
