<?php

namespace Tests\Feature;

use App\Livewire\DivisionHead\InboxPage as DivisionHeadInboxPage;
use App\Livewire\EDManager\InboxPage as EdManagerInboxPage;
use App\Livewire\FarmManager\MeetingReschedulePage;
use App\Livewire\FarmManager\MyRequestsPage;
use App\Models\ProjectRequest;
use App\Models\RequestTransition;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MeetingRescheduleFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function makeUser(string $role): User
    {
        return User::factory()->create(['role' => $role, 'is_active' => true]);
    }

    protected function makeRequestOwnedBy(User $requestor, string $ownerRole, string $status, string $step): ProjectRequest
    {
        $request = ProjectRequest::create([
            'request_number' => 'APIS-2026-RESCHED01',
            'requestor_id' => $requestor->id,
            'requestor_role' => 'farm_manager',
            'current_status' => $status,
            'current_step' => $step,
            'current_owner_role' => $ownerRole,
            'current_owner_id' => null,
            'is_late' => false,
            'is_exception_flow' => false,
            'title' => 'Reschedule Flow Test Project',
            'request_type' => 'Production Building',
            'budget_category' => 'small',
            'farm_name' => 'Reschedule Test Farm',
            'purpose' => 'Testing reschedule flow',
            'date_needed' => now()->addDays(90),
            'description' => 'Reschedule flow test.',
            'preferred_meeting_date' => now()->addDays(10),
            'preferred_meeting_time' => '10:00',
            'submitted_at' => now(),
        ]);

        RequestTransition::create([
            'project_request_id' => $request->id,
            'acted_by_id' => $requestor->id,
            'acted_by_role' => 'farm_manager',
            'action' => 'submitted',
            'from_status' => null,
            'to_status' => $status,
            'from_step' => null,
            'to_step' => $step,
            'from_owner_role' => null,
            'to_owner_role' => $ownerRole,
            'to_owner_id' => null,
            'is_rework' => false,
            'is_exception_path' => false,
            'is_terminal' => false,
            'remarks' => 'Submitted.',
            'context' => [],
            'acted_at' => now(),
        ]);

        return $request;
    }

    public function test_division_head_can_return_a_request_for_reschedule_without_rejecting_it(): void
    {
        $farmManager = $this->makeUser('farm_manager');
        $divisionHead = $this->makeUser('division_head');
        $request = $this->makeRequestOwnedBy($farmManager, 'division_head', 'submitted', 'division_head_review');

        $this->actingAs($divisionHead);

        Livewire::test(DivisionHeadInboxPage::class)
            ->set("remarks.{$request->request_number}", 'Can we move this earlier in the week?')
            ->call('reschedule', ['requestId' => $request->request_number]);

        $request->refresh();

        $this->assertSame('reschedule_requested', $request->current_status);
        $this->assertSame('requestor_reschedule', $request->current_step);
        $this->assertSame('farm_manager', $request->current_owner_role);
        $this->assertSame($farmManager->id, $request->current_owner_id);
        $this->assertSame('Can we move this earlier in the week?', $request->latest_remarks);

        $returnTo = data_get($request->meta, 'reschedule_return');
        $this->assertSame('submitted', $returnTo['status']);
        $this->assertSame('division_head_review', $returnTo['step']);
        $this->assertSame('division_head', $returnTo['owner_role']);

        // Farm Manager now sees the "Update Meeting Schedule" action, not a full edit/withdraw.
        $this->actingAs($farmManager);
        $rows = Livewire::test(MyRequestsPage::class)->get('requests');
        $row = collect($rows)->firstWhere('id', $request->request_number);
        $this->assertTrue($row['awaitingReschedule']);
        $this->assertFalse($row['isEditable']);
    }

    public function test_farm_manager_resubmitting_a_new_schedule_returns_exactly_to_division_head_not_vp(): void
    {
        $farmManager = $this->makeUser('farm_manager');
        $divisionHead = $this->makeUser('division_head');
        $request = $this->makeRequestOwnedBy($farmManager, 'division_head', 'submitted', 'division_head_review');

        $this->actingAs($divisionHead);
        Livewire::test(DivisionHeadInboxPage::class)
            ->call('reschedule', ['requestId' => $request->request_number]);

        $request->refresh();

        $this->actingAs($farmManager);
        Livewire::test(MeetingReschedulePage::class, ['projectRequest' => $request->id])
            ->set('form.mtgDate', now()->addDays(20)->toDateString())
            ->set('form.mtgTime', '15:30')
            ->call('submit')
            ->assertSet('submitted', true);

        $request->refresh();

        $this->assertSame('submitted', $request->current_status);
        $this->assertSame('division_head_review', $request->current_step);
        $this->assertSame('division_head', $request->current_owner_role);
        $this->assertNull($request->current_owner_id);
        $this->assertSame(now()->addDays(20)->toDateString(), $request->preferred_meeting_date->toDateString());
        $this->assertSame('15:30', (string) $request->preferred_meeting_time);
        $this->assertNull(data_get($request->meta, 'reschedule_return'));

        // It's back in Division Head's actionable inbox.
        $this->actingAs($divisionHead);
        $rows = Livewire::test(DivisionHeadInboxPage::class)->get('inboxItems');
        $row = collect($rows)->firstWhere('id', $request->request_number);
        $this->assertNotNull($row);
        $this->assertTrue($row['isPendingHere']);
    }

    public function test_ed_manager_can_also_return_a_request_for_reschedule_and_it_returns_to_ed_manager(): void
    {
        $farmManager = $this->makeUser('farm_manager');
        $edManager = $this->makeUser('ed_manager');
        $request = $this->makeRequestOwnedBy($farmManager, 'ed_manager', 'vp_approved', 'ed_manager_acceptance');

        $this->actingAs($edManager);

        Livewire::test(EdManagerInboxPage::class)
            ->call('reschedule', ['requestId' => $request->request_number]);

        $request->refresh();

        $this->assertSame('reschedule_requested', $request->current_status);
        $this->assertSame('requestor_reschedule', $request->current_step);
        $this->assertSame($farmManager->id, $request->current_owner_id);

        $returnTo = data_get($request->meta, 'reschedule_return');
        $this->assertSame('vp_approved', $returnTo['status']);
        $this->assertSame('ed_manager_acceptance', $returnTo['step']);
        $this->assertSame('ed_manager', $returnTo['owner_role']);

        $this->actingAs($farmManager);
        Livewire::test(MeetingReschedulePage::class, ['projectRequest' => $request->id])
            ->set('form.mtgDate', now()->addDays(25)->toDateString())
            ->set('form.mtgTime', '09:00')
            ->call('submit');

        $request->refresh();

        // Returns to ED Manager specifically, not Division Head or VP.
        $this->assertSame('vp_approved', $request->current_status);
        $this->assertSame('ed_manager_acceptance', $request->current_step);
        $this->assertSame('ed_manager', $request->current_owner_role);
        $this->assertNull($request->current_owner_id);
    }
}
