<?php

namespace App\Livewire\ITAdmin;

use App\Models\HubConnection;
use App\Models\User;
use App\Support\HubClient;
use App\Support\HubConnectionRejected;
use App\Support\HubEnrollmentException;
use App\Support\HubSyncComparison;
use App\Support\UserAccessManager;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Throwable;

class HubSyncPage extends Component
{
    public bool $hasConnection = false;

    /** The one-time code from the hub admin -- never stored, used once. */
    public string $enrollmentCode = '';

    public ?string $enrollmentError = null;

    /** Humanized "N days ago", or null if never synced. */
    public ?string $lastSyncedAt = null;

    public bool $lastSyncedStale = false;

    /** Set when the hub can't be reached -- User Management is unaffected. */
    public ?string $error = null;

    /** True when $error means the connection itself was rejected (401/403). */
    public bool $credentialsRejected = false;

    /** Result summary shown after an apply. */
    public ?string $flash = null;

    /** @var array<int, array<string, mixed>> */
    public array $newPeople = [];

    /** @var array<int, array<string, mixed>> */
    public array $changedPeople = [];

    /** @var array<int, array<string, mixed>> */
    public array $localOnlyPeople = [];

    /** @var array<int, bool> id => selected */
    public array $selectedNew = [];

    /** @var array<int, bool> id => selected */
    public array $selectedChanged = [];

    public function mount(): void
    {
        $this->hasConnection = HubConnection::current() !== null;
        $this->load();
    }

    public function enroll(): void
    {
        $validated = $this->validate([
            'enrollmentCode' => ['required', 'string', 'max:255'],
        ]);

        try {
            app(HubClient::class)->enroll($validated['enrollmentCode']);
        } catch (HubEnrollmentException $exception) {
            $this->enrollmentError = $exception->getMessage();

            return;
        }

        $this->enrollmentCode = '';
        $this->enrollmentError = null;
        $this->hasConnection = true;

        $this->load();

        $this->dispatch('notify', type: 'success', message: 'Connected to the Access Hub.');
    }

    public function resetConnection(): void
    {
        HubConnection::reset();

        $this->hasConnection = false;
        $this->enrollmentError = null;
        $this->error = null;
        $this->credentialsRejected = false;
        $this->flash = null;
        $this->newPeople = $this->changedPeople = $this->localOnlyPeople = [];
        $this->selectedNew = $this->selectedChanged = [];
        $this->lastSyncedAt = null;
        $this->lastSyncedStale = false;

        $this->dispatch('notify', type: 'warn', message: 'Access Hub connection reset. Enroll again to resume syncing.');
    }

    public function refreshPreview(): void
    {
        $this->flash = null;
        $this->load();
    }

    public function apply(): void
    {
        if (! $this->hasConnection || $this->error !== null) {
            return;
        }

        $updated = 0;
        $revoked = 0;

        foreach ($this->newPeople as $row) {
            if (! ($this->selectedNew[$row['id']] ?? false)) {
                continue;
            }

            UserAccessManager::assign($row['id'], self::attributesFrom($row), 'hub');
            $updated++;
        }

        foreach ($this->changedPeople as $row) {
            if (! ($this->selectedChanged[$row['id']] ?? false)) {
                continue;
            }

            $user = User::find($row['id']);

            // Guardrail: sync only ever writes rows it owns, even if the
            // compare somehow handed us one it shouldn't have.
            if ($user === null || $user->source !== 'hub') {
                continue;
            }

            if ($row['revoke'] ?? false) {
                UserAccessManager::revoke($user);
                $revoked++;

                continue;
            }

            UserAccessManager::assign($row['id'], self::attributesFrom($row), 'hub');
            $updated++;
        }

        // So the User Management page re-reads on next visit.
        Cache::forget('users_page_db_users');

        $this->load();
        $this->flash = "Sync applied — {$updated} account(s) updated, {$revoked} revoked.";

        $this->dispatch('notify', type: 'success', message: $this->flash);
    }

    protected function load(): void
    {
        $this->error = null;
        $this->credentialsRejected = false;

        if (! $this->hasConnection) {
            $this->newPeople = $this->changedPeople = $this->localOnlyPeople = [];
            $this->selectedNew = $this->selectedChanged = [];

            return;
        }

        try {
            $people = app(HubClient::class)->fetchGrants();
        } catch (HubConnectionRejected $exception) {
            Log::error('Hub sync rejected: '.$exception->getMessage());

            $this->credentialsRejected = true;
            $this->error = 'The hub rejected this connection. Reset it and enroll again.';
            $this->newPeople = $this->changedPeople = $this->localOnlyPeople = [];
            $this->selectedNew = $this->selectedChanged = [];

            return;
        } catch (Throwable $exception) {
            Log::error('Hub sync fetch failed: '.$exception->getMessage());

            $this->error = 'Could not reach the Access Hub. User Management is unaffected — try again later.';
            $this->newPeople = $this->changedPeople = $this->localOnlyPeople = [];
            $this->selectedNew = $this->selectedChanged = [];

            return;
        }

        $groups = HubSyncComparison::build($people);

        $this->newPeople = $groups['new'];
        $this->changedPeople = $groups['changed'];
        $this->localOnlyPeople = $groups['local_only'];

        // New: safe, tick all. Changed: needs eyes, tick none.
        $this->selectedNew = collect($this->newPeople)
            ->mapWithKeys(fn (array $row) => [$row['id'] => true])
            ->all();

        $this->selectedChanged = collect($this->changedPeople)
            ->mapWithKeys(fn (array $row) => [$row['id'] => false])
            ->all();

        $connection = HubConnection::current();
        $this->lastSyncedAt = $connection?->last_synced_at?->diffForHumans();
        $this->lastSyncedStale = (bool) $connection?->last_synced_at?->lt(now()->subDays(30));
    }

    public function roleLabel(?string $role): string
    {
        return match ($role) {
            null, '' => 'no granted role',
            'farm_manager' => 'Farm Manager',
            'division_head' => 'Division Head',
            'vp_gen_services' => 'VP Gen Services',
            'dh_gen_services' => 'DH Gen Services',
            'ed_manager' => 'ED Manager',
            'it_admin' => 'IT Admin',
            'engineer' => 'Engineer',
            'guest' => 'Guest',
            default => str_replace('_', ' ', ucwords($role, '_')),
        };
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    protected static function attributesFrom(array $row): array
    {
        return [
            'name' => $row['name'],
            'email' => $row['email'],
            'role' => $row['role'],
            'farm' => $row['farm'],
            'department' => $row['department'],
            'position' => $row['position'],
            'active' => true,
        ];
    }

    public function render()
    {
        return view('livewire.it-admin.hub-sync-page')
            ->layout('layouts.app', [
                'title' => 'Sync from Hub | Project Initialization System',
                'header' => 'Sync from Access Hub',
                'subheader' => 'Review what would change before anything is written.',
            ]);
    }
}
