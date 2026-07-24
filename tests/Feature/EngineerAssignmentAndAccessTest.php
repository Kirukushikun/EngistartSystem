<?php

namespace Tests\Feature;

use App\Livewire\DHGenServices\NotingPage as DhNotingPage;
use App\Livewire\EDManager\InboxPage as EdManagerInboxPage;
use App\Livewire\Shared\AssignedEngineersPage;
use App\Models\ProjectRequest;
use App\Models\RequestTransition;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EngineerAssignmentAndAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function makeUser(string $role): User
    {
        return User::factory()->create(['role' => $role, 'is_active' => true]);
    }

    protected function makeAcceptedRequest(User $requestor): ProjectRequest
    {
        $request = ProjectRequest::create([
            'request_number' => 'APIS-2026-ASSIGN01',
            'requestor_id' => $requestor->id,
            'requestor_role' => 'farm_manager',
            'current_status' => 'vp_approved',
            'current_step' => 'ed_manager_acceptance',
            'current_owner_role' => 'ed_manager',
            'current_owner_id' => null,
            'is_late' => false,
            'is_exception_flow' => false,
            'title' => 'Engineer Assignment Test Project',
            'request_type' => 'Production Building',
            'budget_category' => 'small',
            'farm_name' => 'Assignment Test Farm',
            'purpose' => 'Testing engineer assignment relocation',
            'date_needed' => now()->addDays(90),
            'description' => 'Assignment relocation test.',
            'submitted_at' => now(),
        ]);

        RequestTransition::create([
            'project_request_id' => $request->id,
            'acted_by_id' => $requestor->id,
            'acted_by_role' => 'farm_manager',
            'action' => 'submitted',
            'from_status' => null,
            'to_status' => 'vp_approved',
            'from_step' => null,
            'to_step' => 'ed_manager_acceptance',
            'from_owner_role' => null,
            'to_owner_role' => 'ed_manager',
            'to_owner_id' => null,
            'is_rework' => false,
            'is_exception_path' => false,
            'is_terminal' => false,
            'remarks' => 'Setup.',
            'context' => [],
            'acted_at' => now(),
        ]);

        return $request;
    }

    public function test_ed_manager_must_select_an_engineer_before_accepting(): void
    {
        $farmManager = $this->makeUser('farm_manager');
        $ed = $this->makeUser('ed_manager');
        $request = $this->makeAcceptedRequest($farmManager);

        $this->actingAs($ed);

        // No engineer selected: confirmAccept should not open the confirmation modal.
        Livewire::test(EdManagerInboxPage::class)
            ->call('confirmAccept', $request->request_number)
            ->assertNotDispatched('openConfirmationModal');

        $request->refresh();
        $this->assertSame('ed_manager', $request->current_owner_role);
        $this->assertNull($request->assigned_engineer_id);
    }

    public function test_ed_manager_accept_assigns_the_selected_engineer_and_dh_gen_services_forwards_to_them(): void
    {
        $farmManager = $this->makeUser('farm_manager');
        $ed = $this->makeUser('ed_manager');
        $dh = $this->makeUser('dh_gen_services');
        $engineer = $this->makeUser('engineer');
        $request = $this->makeAcceptedRequest($farmManager);

        $this->actingAs($ed);

        Livewire::test(EdManagerInboxPage::class)
            ->set("selectedEngineer.{$request->request_number}", $engineer->id)
            ->call('accept', ['requestId' => $request->request_number]);

        $request->refresh();
        $this->assertSame('accepted', $request->current_status);
        $this->assertSame('dh_gen_services', $request->current_owner_role);
        $this->assertSame($engineer->id, $request->assigned_engineer_id);
        // Ownership hasn't reached the engineer yet — DH Gen Services still needs to note it.
        $this->assertNull($request->current_owner_id);

        $this->actingAs($dh);

        Livewire::test(DhNotingPage::class)
            ->assertOk()
            ->assertSee($engineer->name)
            ->call('noteForward', ['requestId' => $request->request_number]);

        $request->refresh();
        $this->assertSame('noted', $request->current_status);
        $this->assertSame('engineer', $request->current_owner_role);
        $this->assertSame($engineer->id, $request->current_owner_id);
        $this->assertSame($engineer->id, $request->assigned_engineer_id);
    }

    public function test_dh_gen_services_noting_does_not_forward_if_somehow_no_engineer_was_assigned(): void
    {
        $farmManager = $this->makeUser('farm_manager');
        $dh = $this->makeUser('dh_gen_services');
        $request = $this->makeAcceptedRequest($farmManager);
        $request->update(['current_owner_role' => 'dh_gen_services', 'current_status' => 'accepted']);

        $this->actingAs($dh);

        try {
            Livewire::test(DhNotingPage::class)
                ->call('noteForward', ['requestId' => $request->request_number]);
        } catch (\Throwable) {
            // Expected: the safety guard rejects noting an unassigned request. Exact exception
            // shape depends on how Livewire surfaces abort_if() from within a component method;
            // what matters is that the request was never advanced past DH Gen Services.
        }

        $request->refresh();
        $this->assertSame('accepted', $request->current_status);
        $this->assertSame('dh_gen_services', $request->current_owner_role);
        $this->assertNull($request->assigned_engineer_id);
    }

    public function test_ed_manager_can_access_assigned_engineers_module(): void
    {
        $ed = $this->makeUser('ed_manager');
        $this->actingAs($ed);

        $this->get(route('ed-manager.assigned-engineers'))->assertOk();

        Livewire::test(AssignedEngineersPage::class)->assertOk();
    }

    public function test_other_roles_are_not_granted_the_ed_manager_route(): void
    {
        $divisionHead = $this->makeUser('division_head');
        $this->actingAs($divisionHead);

        $this->get(route('ed-manager.assigned-engineers'))->assertForbidden();
    }

    public function test_ed_manager_can_create_a_new_engineer_account(): void
    {
        $ed = $this->makeUser('ed_manager');
        $this->actingAs($ed);

        Livewire::test(AssignedEngineersPage::class)
            ->call('createEngineer')
            ->assertSet('formMode', 'create')
            ->set('form.name', 'Engineer Four')
            ->set('form.email', 'engineer.four@brooksidegroup.org')
            ->set('form.password', 'password123')
            ->set('form.password_confirmation', 'password123')
            ->call('save')
            ->assertSet('formMode', null);

        $engineer = User::query()->where('email', 'engineer.four@brooksidegroup.org')->firstOrFail();
        $this->assertSame('engineer', $engineer->role);
        $this->assertTrue($engineer->is_active);

        // Newly created account is immediately visible in the ED Manager's own listing.
        Livewire::test(AssignedEngineersPage::class)
            ->assertOk()
            ->assertSee('Engineer Four')
            ->assertSee('engineer.four@brooksidegroup.org');
    }
}
