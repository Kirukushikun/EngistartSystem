<?php

namespace App\Support;

use App\Models\HubConnection;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

/**
 * Talks to BFC's Access Hub: one-time enrollment, then the recurring grants
 * fetch. Every call is timed out; any failure throws rather than returning a
 * partial/empty result, so a caller can never mistake "couldn't reach the
 * hub" for "the hub says nobody has access".
 */
class HubClient
{
    /**
     * Exchanges a one-time enrollment code for a client id/secret pair and
     * stores it as this system's connection, replacing any prior one.
     *
     * @throws HubEnrollmentException
     */
    public function enroll(string $code): HubConnection
    {
        $projectKey = (string) config('hub.project_key');

        if ($projectKey === '') {
            throw new HubEnrollmentException(
                'HUB_PROJECT_KEY is not configured. Get this project\'s registered key from the hub admin first.'
            );
        }

        try {
            $response = Http::withOptions(['verify' => storage_path('cacert.pem')])
                ->timeout(10)
                ->connectTimeout(5)
                ->post($this->baseUrl().'/api/v1/enroll', [
                    'code' => $code,
                    'project_key' => $projectKey,
                    'environment' => app()->environment(),
                ]);
        } catch (Throwable $exception) {
            throw new HubEnrollmentException(
                'Could not reach the Access Hub to enroll. Try again shortly.',
                previous: $exception
            );
        }

        match ($response->status()) {
            404 => throw new HubEnrollmentException('Enrollment failed: the hub does not recognize this project key.'),
            422 => throw new HubEnrollmentException('Enrollment failed: the code is wrong, expired, or already used.'),
            429 => throw new HubEnrollmentException('The hub is rate-limiting enrollment attempts. Wait a moment and try again.'),
            default => null,
        };

        if (! $response->successful()) {
            throw new HubEnrollmentException("Enrollment failed: the hub responded with status {$response->status()}.");
        }

        $clientId = (string) $response->json('client_id');
        $clientSecret = (string) $response->json('client_secret');

        if ($clientId === '' || $clientSecret === '') {
            throw new HubEnrollmentException('Enrollment succeeded but the response was missing client_id/client_secret.');
        }

        return HubConnection::replace($clientId, $clientSecret);
    }

    /**
     * The hub's `people` array: one entry per person with user_id, name,
     * email, farm, department, position, roles (always an array), active.
     *
     * @return array<int, array<string, mixed>>
     *
     * @throws HubConnectionRejected  the connection was rejected (401/403) -- reset and re-enroll
     * @throws RuntimeException  not connected yet, misconfigured, or any other failure
     */
    public function fetchGrants(): array
    {
        $connection = HubConnection::current();

        if ($connection === null) {
            throw new RuntimeException('No Access Hub connection configured yet.');
        }

        try {
            $response = Http::withHeaders([
                    'X-Client-Id' => $connection->client_id,
                    'X-Client-Secret' => $connection->client_secret,
                ])
                ->withOptions(['verify' => storage_path('cacert.pem')])
                ->timeout(10)
                ->connectTimeout(5)
                ->get($this->baseUrl().'/api/v1/grants');
        } catch (Throwable $exception) {
            throw new RuntimeException('Could not reach the Access Hub: '.$exception->getMessage(), previous: $exception);
        }

        if (in_array($response->status(), [401, 403], true)) {
            throw new HubConnectionRejected('The hub rejected this connection (revoked or invalid).');
        }

        if (! $response->successful()) {
            throw new RuntimeException("Hub responded with status {$response->status()}.");
        }

        $decoded = $response->json();

        if (! is_array($decoded) || ! isset($decoded['people']) || ! is_array($decoded['people'])) {
            throw new RuntimeException('Hub response is malformed: expected a "people" array.');
        }

        // Stamped on a successful fetch, not on apply -- guide 4.7: a sync
        // that finds nothing to change still means the system is current.
        $connection->forceFill(['last_synced_at' => now()])->save();

        return array_values($decoded['people']);
    }

    protected function baseUrl(): string
    {
        $baseUrl = rtrim((string) config('hub.base_url'), '/');

        if ($baseUrl === '') {
            throw new RuntimeException('HUB_BASE_URL is not configured.');
        }

        return $baseUrl;
    }
}
