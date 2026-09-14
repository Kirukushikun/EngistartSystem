<?php

namespace Tests\Feature;

use App\Livewire\Shared\AssignedEngineersPage;
use App\Models\ProjectRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Livewire\Livewire;
use Tests\TestCase;

class EngineerEditAndDeleteTest extends TestCase
{
    use RefreshDatabase;

    protected function itAdmin(): User
    {
        return User::factory()->create(['role' => 'it_admin', 'is_active' => true]);
    }

    protected function engineer(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'role' => 'engineer',
            'is_active' => true,
        ], $overrides));
    }

    protected function pendingRequestFor(User $engineer): ProjectRequest
    {
        return ProjectRequest::create([
            'request_number' => 'APIS-2026-DEL'.$engineer->id,
            'requestor_id' => User::factory()->create(['role' => 'farm_manager'])->id,
            'requestor_role' => 'farm_manager',
            'current_status' => 'accepted',
            'current_step' => null,
            'current_owner_role' => 'engineer',
            'current_owner_id' => $engineer->id,
            'assigned_engineer_id' => $engineer->id,
            'is_late' => false,
            'is_exception_flow' => false,
            'title' => 'Pending Init Test',
            'request_type' => 'Building',
            'budget_category' => 'small',
            'farm_name' => 'Test Farm',
            'purpose' => 'Delete guard test.',
            'date_needed' => now()->addDays(90),
            'description' => 'Delete guard test.',
            'submitted_at' => now(),
        ]);
    }

    // ---- edit --------------------------------------------------------

    public function test_edit_updates_name_and_email(): void
    {
        $engineer = $this->engineer(['name' => 'Old Name', 'email' => 'old@bfcgroup.org']);

        Livewire::actingAs($this->itAdmin())
            ->test(AssignedEngineersPage::class)
            ->call('editEngineer', $engineer->id)
            ->assertSet('formMode', 'edit')
            ->set('form.name', 'New Name')
            ->set('form.email', 'new@bfcgroup.org')
            ->call('save')
            ->assertHasNoErrors();

        $engineer->refresh();
        $this->assertSame('New Name', $engineer->name);
        $this->assertSame('new@bfcgroup.org', $engineer->email);
    }

    public function test_edit_does_not_touch_the_password(): void
    {
        $engineer = $this->engineer();
        $originalHash = $engineer->password;

        Livewire::actingAs($this->itAdmin())
            ->test(AssignedEngineersPage::class)
            ->call('editEngineer', $engineer->id)
            ->set('form.name', 'Renamed')
            ->call('save');

        $this->assertSame($originalHash, $engineer->refresh()->password);
    }

    public function test_edit_rejects_an_email_already_used_by_someone_else(): void
    {
        $this->engineer(['email' => 'taken@bfcgroup.org']);
        $engineer = $this->engineer(['email' => 'mine@bfcgroup.org']);

        Livewire::actingAs($this->itAdmin())
            ->test(AssignedEngineersPage::class)
            ->call('editEngineer', $engineer->id)
            ->set('form.email', 'taken@bfcgroup.org')
            ->call('save')
            ->assertHasErrors(['form.email']);

        $this->assertSame('mine@bfcgroup.org', $engineer->refresh()->email);
    }

    public function test_edit_allows_keeping_the_engineers_own_current_email(): void
    {
        $engineer = $this->engineer(['email' => 'same@bfcgroup.org']);

        Livewire::actingAs($this->itAdmin())
            ->test(AssignedEngineersPage::class)
            ->call('editEngineer', $engineer->id)
            ->set('form.name', 'Renamed Only')
            ->set('form.email', 'same@bfcgroup.org')
            ->call('save')
            ->assertHasNoErrors();
    }

    // ---- delete --------------------------------------------------------

    public function test_delete_opens_the_confirmation_dialog_with_a_clean_engineer(): void
    {
        $engineer = $this->engineer();

        Livewire::actingAs($this->itAdmin())
            ->test(AssignedEngineersPage::class)
            ->call('confirmDeleteEngineer', $engineer->id)
            ->assertDispatched('openConfirmationModal');

        $this->assertNotNull(User::find($engineer->id)); // not deleted yet -- only the dialog opened
    }

    public function test_confirming_delete_removes_the_account(): void
    {
        $engineer = $this->engineer();

        Livewire::actingAs($this->itAdmin())
            ->test(AssignedEngineersPage::class)
            ->call('deleteEngineer', ['userId' => $engineer->id])
            ->assertDispatched('notify', type: 'success');

        $this->assertNull(User::find($engineer->id));
    }

    public function test_delete_opens_the_reassignment_panel_when_the_engineer_has_a_pending_request(): void
    {
        $engineer = $this->engineer();
        $request = $this->pendingRequestFor($engineer);

        Livewire::actingAs($this->itAdmin())
            ->test(AssignedEngineersPage::class)
            ->call('confirmDeleteEngineer', $engineer->id)
            ->assertNotDispatched('openConfirmationModal')
            ->assertSet('formMode', 'reassign')
            ->assertSet('pendingRequests', fn ($rows) => collect($rows)->pluck('request_number')->contains($request->request_number));

        $this->assertNotNull(User::find($engineer->id));
    }

    public function test_reassigning_the_only_pending_request_lets_delete_proceed(): void
    {
        $engineer = $this->engineer();
        $replacement = $this->engineer(['name' => 'Replacement Engineer']);
        $request = $this->pendingRequestFor($engineer);

        $component = Livewire::actingAs($this->itAdmin())
            ->test(AssignedEngineersPage::class)
            ->call('confirmDeleteEngineer', $engineer->id)
            ->set("reassignPicks.{$request->request_number}", $replacement->id)
            ->call('reassignRequest', $request->request_number)
            ->assertDispatched('notify', type: 'success')
            ->assertSet('pendingRequests', []);

        $request->refresh();
        $this->assertSame($replacement->id, $request->current_owner_id);
        $this->assertSame($replacement->id, $request->assigned_engineer_id);
        $this->assertSame('engineer', $request->current_owner_role);

        // Now nothing blocks deletion.
        $component->call('confirmDeleteEngineer', $engineer->id)
            ->assertDispatched('openConfirmationModal');
    }

    public function test_reassign_requires_picking_an_engineer_first(): void
    {
        $engineer = $this->engineer();
        $request = $this->pendingRequestFor($engineer);

        Livewire::actingAs($this->itAdmin())
            ->test(AssignedEngineersPage::class)
            ->call('confirmDeleteEngineer', $engineer->id)
            ->call('reassignRequest', $request->request_number)
            ->assertDispatched('notify', type: 'warn');

        $this->assertSame($engineer->id, $request->refresh()->current_owner_id);
    }

    public function test_removing_the_assignment_sends_the_request_back_to_ed_manager(): void
    {
        $engineer = $this->engineer();
        $request = $this->pendingRequestFor($engineer);

        Livewire::actingAs($this->itAdmin())
            ->test(AssignedEngineersPage::class)
            ->call('confirmDeleteEngineer', $engineer->id)
            ->call('removeAssignment', $request->request_number)
            ->assertDispatched('notify', type: 'warn')
            ->assertSet('pendingRequests', []);

        $request->refresh();
        $this->assertSame('ed_manager', $request->current_owner_role);
        $this->assertNull($request->current_owner_id);
        $this->assertNull($request->assigned_engineer_id);
        $this->assertSame('vp_approved', $request->current_status);
        $this->assertSame('ed_manager_acceptance', $request->current_step);
    }

    public function test_removed_assignment_is_immediately_actionable_by_ed_manager(): void
    {
        $engineer = $this->engineer();
        $request = $this->pendingRequestFor($engineer);

        Livewire::actingAs($this->itAdmin())
            ->test(AssignedEngineersPage::class)
            ->call('confirmDeleteEngineer', $engineer->id)
            ->call('removeAssignment', $request->request_number);

        // Exactly ED Manager's own "awaiting acceptance" query shape.
        $this->assertTrue(
            ProjectRequest::query()
                ->where('request_number', $request->request_number)
                ->where('current_owner_role', 'ed_manager')
                ->whereNull('withdrawn_at')
                ->exists()
        );
    }

    public function test_delete_is_refused_when_the_engineer_has_a_pending_request(): void
    {
        $engineer = $this->engineer();
        $this->pendingRequestFor($engineer);

        Livewire::actingAs($this->itAdmin())
            ->test(AssignedEngineersPage::class)
            ->call('confirmDeleteEngineer', $engineer->id)
            ->assertNotDispatched('openConfirmationModal');

        $this->assertNotNull(User::find($engineer->id));
    }

    public function test_delete_confirmation_re_checks_pending_work_in_case_it_changed(): void
    {
        $engineer = $this->engineer();

        // Simulates the dialog having been opened before the request landed.
        $this->pendingRequestFor($engineer);

        Livewire::actingAs($this->itAdmin())
            ->test(AssignedEngineersPage::class)
            ->call('deleteEngineer', ['userId' => $engineer->id])
            ->assertDispatched('notify', type: 'danger');

        $this->assertNotNull(User::find($engineer->id));
    }

    public function test_deleting_an_engineer_nulls_out_their_past_assignment_instead_of_erroring(): void
    {
        $engineer = $this->engineer();

        $request = ProjectRequest::create([
            'request_number' => 'APIS-2026-DELHIST',
            'requestor_id' => User::factory()->create(['role' => 'farm_manager'])->id,
            'requestor_role' => 'farm_manager',
            'current_status' => 'initialized',
            'current_owner_role' => 'farm_manager',
            'current_owner_id' => null,
            'assigned_engineer_id' => $engineer->id,
            'is_late' => false,
            'is_exception_flow' => false,
            'title' => 'Completed Project',
            'request_type' => 'Building',
            'budget_category' => 'small',
            'farm_name' => 'Test Farm',
            'purpose' => 'Historical record.',
            'date_needed' => now()->addDays(90),
            'description' => 'Historical record.',
            'submitted_at' => now(),
        ]);

        Livewire::actingAs($this->itAdmin())
            ->test(AssignedEngineersPage::class)
            ->call('deleteEngineer', ['userId' => $engineer->id]);

        $this->assertNull(User::find($engineer->id));
        $this->assertNull($request->refresh()->assigned_engineer_id);
    }

    public function test_an_engineer_cannot_delete_their_own_account(): void
    {
        $engineer = $this->engineer();

        Livewire::actingAs($engineer)
            ->test(AssignedEngineersPage::class)
            ->call('confirmDeleteEngineer', $engineer->id)
            ->assertNotDispatched('openConfirmationModal')
            ->assertDispatched('notify', type: 'danger');

        $this->assertNotNull(User::find($engineer->id));
    }
}
