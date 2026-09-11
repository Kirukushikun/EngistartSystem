<?php

namespace Tests\Feature;

use App\Livewire\ITAdmin\HubSyncPage;
use App\Models\HubConnection;
use App\Models\User;
use App\Support\HubClient;
use App\Support\HubConnectionRejected;
use App\Support\HubEnrollmentException;
use App\Support\HubRoleResolver;
use App\Support\HubSyncComparison;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Livewire\Livewire;
use RuntimeException;
use Tests\TestCase;

class HubSyncTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A stored connection is a precondition for every one of these -- in real
     * use fetchGrants() is never called before enrollment.
     */
    protected function seedConnection(): void
    {
        HubConnection::create(['client_id' => 'chub_test', 'client_secret' => 'secret']);
    }

    /**
     * @param  array<int, array<string, mixed>>  $people
     */
    protected function fakeHub(array $people): void
    {
        $this->seedConnection();

        $this->app->bind(HubClient::class, fn () => new class($people) extends HubClient
        {
            /** @param array<int, array<string, mixed>> $people */
            public function __construct(private array $people)
            {
            }

            public function fetchGrants(): array
            {
                return $this->people;
            }
        });
    }

    protected function hubDown(): void
    {
        $this->seedConnection();

        $this->app->bind(HubClient::class, fn () => new class extends HubClient
        {
            public function fetchGrants(): array
            {
                throw new RuntimeException('hub unreachable');
            }
        });
    }

    protected function hubRejectsConnection(): void
    {
        $this->seedConnection();

        $this->app->bind(HubClient::class, fn () => new class extends HubClient
        {
            public function fetchGrants(): array
            {
                throw new HubConnectionRejected('revoked');
            }
        });
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    protected function person(int $id, array $overrides = []): array
    {
        return array_merge([
            'user_id' => $id,
            'name' => "Person {$id}",
            'email' => "person{$id}@bfcgroup.org",
            'farm' => 'Farm X',
            'department' => 'Operations',
            'position' => 'Officer',
            'roles' => ['manager'],
            'active' => true,
        ], $overrides);
    }

    protected function itAdmin(): User
    {
        return User::factory()->create(['id' => 900, 'role' => 'it_admin', 'source' => 'manual']);
    }

    // ---- resolver ---------------------------------------------------------

    public function test_resolver_picks_the_highest_privilege_role(): void
    {
        $this->assertSame('vp_gen_services', HubRoleResolver::resolve(['manager', 'vp']));
        $this->assertSame('division_head', HubRoleResolver::resolve(['manager', 'division_head']));
        $this->assertSame('farm_manager', HubRoleResolver::resolve(['manager']));
    }

    public function test_resolver_returns_null_when_nothing_maps(): void
    {
        $this->assertNull(HubRoleResolver::resolve(['user']));
        $this->assertNull(HubRoleResolver::resolve([]));
    }

    public function test_resolver_skips_an_unknown_role_string_but_keeps_the_person(): void
    {
        Log::spy();

        $this->assertSame('farm_manager', HubRoleResolver::resolve(['auditor', 'manager']));

        Log::shouldHaveReceived('warning')->withArgs(fn ($m) => str_contains($m, 'auditor'))->once();
    }

    // ---- compare ---------------------------------------------------------

    public function test_new_active_mapped_person_lands_in_new(): void
    {
        $groups = HubSyncComparison::build([$this->person(412, ['roles' => ['manager']])]);

        $this->assertCount(1, $groups['new']);
        $this->assertSame(412, $groups['new'][0]['id']);
        $this->assertSame('farm_manager', $groups['new'][0]['role']);
        $this->assertSame([], $groups['changed']);
    }

    public function test_new_person_with_only_an_unmapped_role_is_skipped_entirely(): void
    {
        $groups = HubSyncComparison::build([$this->person(1, ['roles' => ['user']])]);

        $this->assertSame([], $groups['new']);
        $this->assertSame([], $groups['changed']);
    }

    public function test_new_inactive_person_is_skipped(): void
    {
        $groups = HubSyncComparison::build([$this->person(1, ['active' => false])]);

        $this->assertSame([], $groups['new']);
    }

    public function test_hub_owned_row_with_a_role_change_lands_in_changed(): void
    {
        User::factory()->create([
            'id' => 412, 'name' => 'Person 412', 'email' => 'person412@bfcgroup.org',
            'role' => 'farm_manager', 'is_active' => true, 'source' => 'hub',
        ]);

        $groups = HubSyncComparison::build([$this->person(412, ['roles' => ['division_head']])]);

        $this->assertCount(1, $groups['changed']);
        $this->assertFalse($groups['changed'][0]['revoke']);
        $this->assertSame('division_head', $groups['changed'][0]['role']);
        $this->assertSame('farm_manager', $groups['changed'][0]['current']['role']);
    }

    public function test_hub_owned_row_gone_inactive_is_a_revoke(): void
    {
        User::factory()->create([
            'id' => 412, 'role' => 'farm_manager', 'is_active' => true, 'source' => 'hub',
        ]);

        $groups = HubSyncComparison::build([$this->person(412, ['active' => false])]);

        $this->assertCount(1, $groups['changed']);
        $this->assertTrue($groups['changed'][0]['revoke']);
    }

    public function test_hub_owned_row_whose_roles_all_became_unmapped_is_a_revoke(): void
    {
        User::factory()->create([
            'id' => 412, 'role' => 'farm_manager', 'is_active' => true, 'source' => 'hub',
        ]);

        $groups = HubSyncComparison::build([$this->person(412, ['roles' => ['user'], 'active' => true])]);

        $this->assertCount(1, $groups['changed']);
        $this->assertTrue($groups['changed'][0]['revoke']);
    }

    public function test_a_manual_row_the_hub_also_lists_is_never_a_candidate_for_change(): void
    {
        User::factory()->create([
            'id' => 412, 'role' => 'guest', 'is_active' => true, 'source' => 'manual',
        ]);

        $groups = HubSyncComparison::build([$this->person(412, ['roles' => ['division_head']])]);

        $this->assertSame([], $groups['new']);
        $this->assertSame([], $groups['changed']);
        $this->assertSame([], $groups['local_only']); // the hub does mention them
    }

    public function test_a_local_row_the_hub_does_not_mention_is_local_only(): void
    {
        User::factory()->create(['id' => 5, 'name' => 'Homegrown', 'role' => 'it_admin', 'source' => 'manual']);

        $groups = HubSyncComparison::build([$this->person(412)]);

        $this->assertCount(1, $groups['local_only']);
        $this->assertSame(5, $groups['local_only'][0]['id']);
    }

    public function test_an_identical_hub_owned_row_produces_no_change(): void
    {
        User::factory()->create([
            'id' => 412, 'name' => 'Person 412', 'email' => 'person412@bfcgroup.org',
            'role' => 'farm_manager', 'farm' => 'Farm X', 'department' => 'Operations',
            'position' => 'Officer', 'is_active' => true, 'source' => 'hub',
        ]);

        $groups = HubSyncComparison::build([$this->person(412, ['roles' => ['manager']])]);

        $this->assertSame([], $groups['changed']);
    }

    // ---- apply ----------------------------------------------------------

    public function test_applying_a_new_person_creates_the_row_as_hub_owned(): void
    {
        $this->fakeHub([$this->person(412, [
            'roles' => ['manager'], 'farm' => 'Farm Q', 'department' => 'Poultry', 'position' => 'Supervisor',
        ])]);

        Livewire::actingAs($this->itAdmin())
            ->test(HubSyncPage::class)
            ->call('apply')
            ->assertHasNoErrors();

        $created = User::find(412);
        $this->assertNotNull($created);
        $this->assertSame(412, $created->id);
        $this->assertSame('hub', $created->source);
        $this->assertSame('farm_manager', $created->role);
        $this->assertSame('Farm Q', $created->farm);
        $this->assertSame('Poultry', $created->department);
        $this->assertSame('Supervisor', $created->position);
        $this->assertTrue((bool) $created->is_active);
    }

    public function test_an_unticked_new_person_is_not_applied(): void
    {
        $this->fakeHub([$this->person(412)]);

        Livewire::actingAs($this->itAdmin())
            ->test(HubSyncPage::class)
            ->set('selectedNew.412', false)
            ->call('apply');

        $this->assertNull(User::find(412));
    }

    public function test_applying_a_change_updates_only_a_hub_owned_row(): void
    {
        User::factory()->create([
            'id' => 412, 'role' => 'farm_manager', 'is_active' => true, 'source' => 'hub',
        ]);

        $this->fakeHub([$this->person(412, ['roles' => ['vp']])]);

        Livewire::actingAs($this->itAdmin())
            ->test(HubSyncPage::class)
            ->set('selectedChanged.412', true)
            ->call('apply');

        $this->assertSame('vp_gen_services', User::find(412)->role);
        $this->assertSame('hub', User::find(412)->source);
    }

    public function test_applying_a_revoke_disables_the_row(): void
    {
        User::factory()->create([
            'id' => 412, 'role' => 'farm_manager', 'is_active' => true, 'source' => 'hub',
        ]);

        $this->fakeHub([$this->person(412, ['active' => false])]);

        Livewire::actingAs($this->itAdmin())
            ->test(HubSyncPage::class)
            ->set('selectedChanged.412', true)
            ->call('apply');

        $this->assertFalse((bool) User::find(412)->is_active);
    }

    public function test_applying_reinstates_a_previously_revoked_hub_row(): void
    {
        User::factory()->create([
            'id' => 412, 'role' => 'farm_manager', 'is_active' => false, 'source' => 'hub',
        ]);

        $this->fakeHub([$this->person(412, ['roles' => ['manager'], 'active' => true])]);

        Livewire::actingAs($this->itAdmin())
            ->test(HubSyncPage::class)
            ->set('selectedChanged.412', true)
            ->call('apply');

        $this->assertTrue((bool) User::find(412)->is_active);
    }

    public function test_a_manual_row_is_untouched_even_if_selected_state_is_forced(): void
    {
        $manual = User::factory()->create([
            'id' => 412, 'name' => 'Hand Made', 'role' => 'guest',
            'farm' => 'BFC', 'is_active' => true, 'source' => 'manual',
        ]);

        // Hub lists them with a different role; compare drops them, apply must too.
        $this->fakeHub([$this->person(412, ['roles' => ['vp'], 'farm' => 'Farm Z'])]);

        Livewire::actingAs($this->itAdmin())
            ->test(HubSyncPage::class)
            ->set('selectedChanged.412', true)
            ->call('apply');

        $manual->refresh();
        $this->assertSame('guest', $manual->role);
        $this->assertSame('BFC', $manual->farm);
        $this->assertSame('manual', $manual->source);
    }

    // ---- isolation ------------------------------------------------------

    public function test_a_hub_outage_shows_an_error_and_writes_nothing(): void
    {
        $this->hubDown();

        User::factory()->create(['id' => 7, 'role' => 'farm_manager', 'source' => 'manual']);

        $component = Livewire::actingAs($this->itAdmin())
            ->test(HubSyncPage::class)
            ->assertSet('error', fn ($e) => $e !== null)
            ->assertSet('newPeople', [])
            ->assertSet('changedPeople', []);

        // apply is a no-op while errored
        $component->call('apply');

        $this->assertSame(2, User::count()); // it admin + the untouched manual row
    }

    public function test_a_rejected_connection_offers_reset_instead_of_retry(): void
    {
        $this->hubRejectsConnection();

        Livewire::actingAs($this->itAdmin())
            ->test(HubSyncPage::class)
            ->assertSet('credentialsRejected', true)
            ->assertSet('error', fn ($e) => $e !== null);
    }

    // ---- connection lifecycle --------------------------------------------

    public function test_the_panel_shows_the_enrollment_form_when_there_is_no_connection_yet(): void
    {
        Livewire::actingAs($this->itAdmin())
            ->test(HubSyncPage::class)
            ->assertSet('hasConnection', false)
            ->assertSet('error', null); // no connection isn't an "error" state
    }

    public function test_enroll_stores_the_connection_and_loads_the_preview(): void
    {
        $this->app->bind(HubClient::class, fn () => new class extends HubClient
        {
            public function enroll(string $code): HubConnection
            {
                return HubConnection::replace('chub_new', 'secret');
            }

            public function fetchGrants(): array
            {
                return [];
            }
        });

        Livewire::actingAs($this->itAdmin())
            ->test(HubSyncPage::class)
            ->set('enrollmentCode', 'HUB-TEST-CODE')
            ->call('enroll')
            ->assertSet('hasConnection', true)
            ->assertSet('enrollmentError', null)
            ->assertSet('enrollmentCode', '');

        $this->assertNotNull(HubConnection::current());
        $this->assertSame('chub_new', HubConnection::current()->client_id);
    }

    public function test_a_failed_enrollment_shows_the_error_and_creates_no_connection(): void
    {
        $this->app->bind(HubClient::class, fn () => new class extends HubClient
        {
            public function enroll(string $code): HubConnection
            {
                throw new HubEnrollmentException('the code is wrong, expired, or already used.');
            }
        });

        Livewire::actingAs($this->itAdmin())
            ->test(HubSyncPage::class)
            ->set('enrollmentCode', 'HUB-BAD-CODE')
            ->call('enroll')
            ->assertSet('hasConnection', false)
            ->assertSet('enrollmentError', fn ($e) => str_contains($e, 'wrong, expired'));

        $this->assertNull(HubConnection::current());
    }

    public function test_reset_connection_clears_the_row_and_returns_to_enrollment(): void
    {
        $this->fakeHub([$this->person(412)]);

        $component = Livewire::actingAs($this->itAdmin())
            ->test(HubSyncPage::class)
            ->assertSet('hasConnection', true);

        $component->call('resetConnection')
            ->assertSet('hasConnection', false)
            ->assertSet('newPeople', [])
            ->assertSet('error', null);

        $this->assertNull(HubConnection::current());
    }

    public function test_the_staleness_flag_reflects_a_last_synced_at_older_than_thirty_days(): void
    {
        $this->fakeHub([]);
        // fakeHub()'s stand-in fetchGrants() never touches last_synced_at (see
        // HubClientTest for that) -- stamp it directly to exercise the flag.
        HubConnection::current()->forceFill(['last_synced_at' => now()->subDays(45)])->save();

        Livewire::actingAs($this->itAdmin())
            ->test(HubSyncPage::class)
            ->call('refreshPreview')
            ->assertSet('lastSyncedStale', true);
    }

    public function test_the_staleness_flag_is_false_within_thirty_days(): void
    {
        $this->fakeHub([]);
        HubConnection::current()->forceFill(['last_synced_at' => now()->subDays(5)])->save();

        Livewire::actingAs($this->itAdmin())
            ->test(HubSyncPage::class)
            ->call('refreshPreview')
            ->assertSet('lastSyncedStale', false);
    }
}
