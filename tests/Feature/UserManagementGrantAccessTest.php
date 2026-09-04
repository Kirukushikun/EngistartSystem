<?php

namespace Tests\Feature;

use App\Livewire\ITAdmin\UsersPage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Drives the IT Admin User Management panel the way production runs it:
 * testing mode OFF, so the roster comes from the external directory API.
 */
class UserManagementGrantAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function useRealDirectoryMode(): void
    {
        // A Turnstile secret is what takes the system out of testing mode.
        config()->set('services.turnstile.secret', 'test-secret');
        config()->set('auth.api.base_uri', 'https://auth.example.test');
        config()->set('auth.api.auth_user_api_key', 'test-key');

        Cache::flush();
    }

    /**
     * Shaped like the real listing API: a bare array, split names, encrypted id.
     */
    protected function fakeDirectory(array $records): void
    {
        Http::fake([
            '*/api/v1/users' => Http::response(array_map(fn (array $r) => [
                'id' => Crypt::encryptString((string) $r['id']),
                'first_name' => $r['first_name'],
                'last_name' => $r['last_name'],
                'email' => $r['email'],
            ], $records), 200),
        ]);
    }

    public function test_granting_access_creates_the_user_and_the_row_stops_saying_no_access(): void
    {
        $this->useRealDirectoryMode();

        $itAdmin = User::factory()->create(['id' => 900, 'role' => 'it_admin']);

        $this->fakeDirectory([
            ['id' => 61, 'first_name' => 'Ivan', 'last_name' => 'Guno', 'email' => 'i.guno@bfcgroup.org'],
        ]);

        $component = Livewire::actingAs($itAdmin)->test(UsersPage::class);

        // The directory row shows up and is grantable.
        $component->assertSet('formMode', null);
        $this->assertSame('no access', $component->instance()->users->firstWhere('id', 61)['status']);

        $component->call('grantAccess', 61)
            ->assertSet('formMode', 'grant')
            ->set('form.role', 'farm_manager')
            ->set('form.farm', 'BFC')
            ->set('form.department', 'Poultry')
            ->call('saveUser')
            ->assertHasNoErrors();

        // The row must actually be created...
        $created = User::find(61);
        $this->assertNotNull($created, 'Grant Access did not create the local user row.');
        $this->assertSame('farm_manager', $created->role);
        $this->assertSame('BFC', $created->farm);
        $this->assertSame('Poultry', $created->department);

        // ...and the table must reflect it instead of still saying "no access".
        $row = $component->instance()->users->firstWhere('id', 61);
        $this->assertSame('active', $row['status'], 'Row still shows "no access" after granting.');
        $this->assertSame('farm_manager', $row['role']);
    }

    public function test_granted_access_survives_a_subsequent_livewire_request(): void
    {
        $this->useRealDirectoryMode();

        $itAdmin = User::factory()->create(['id' => 900, 'role' => 'it_admin']);

        $this->fakeDirectory([
            ['id' => 61, 'first_name' => 'Ivan', 'last_name' => 'Guno', 'email' => 'i.guno@bfcgroup.org'],
        ]);

        // Already has access before the page is ever opened.
        User::factory()->create([
            'id' => 61,
            'name' => 'Ivan Guno',
            'email' => 'i.guno@bfcgroup.org',
            'role' => 'farm_manager',
            'is_active' => true,
        ]);

        $component = Livewire::actingAs($itAdmin)->test(UsersPage::class);

        $this->assertSame('active', $component->instance()->users->firstWhere('id', 61)['status']);

        // A second round-trip (any wire action) must not lose that mapping.
        $component->set('search', 'Ivan');

        $row = $component->instance()->users->firstWhere('id', 61);
        $this->assertSame('active', $row['status'], 'Access mapping was lost on the second Livewire request.');
    }
}
