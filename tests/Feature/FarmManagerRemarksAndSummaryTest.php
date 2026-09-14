<?php

namespace Tests\Feature;

use App\Livewire\FarmManager\MyRequestsPage;
use App\Livewire\Shared\RequestSummaryPage;
use App\Models\ProjectRequest;
use App\Models\RequestTransition;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FarmManagerRemarksAndSummaryTest extends TestCase
{
    use RefreshDatabase;

    protected function makeUser(string $role): User
    {
        return User::factory()->create(['role' => $role, 'is_active' => true]);
    }

    protected function makeRequest(User $requestor, string $requestNumber): ProjectRequest
    {
        return ProjectRequest::create([
            'request_number' => $requestNumber,
            'requestor_id' => $requestor->id,
            'requestor_role' => 'farm_manager',
            'current_status' => 'recommended',
            'current_step' => 'vp_gen_services_approval',
            'current_owner_role' => 'vp_gen_services',
            'current_owner_id' => null,
            'is_late' => false,
            'is_exception_flow' => false,
            'title' => 'Remarks/Summary Test',
            'request_type' => 'Production Building',
            'budget_category' => 'small',
            'farm_name' => 'Remarks Test Farm',
            'purpose' => 'Testing remarks collapsibility and summary scoping',
            'date_needed' => now()->addDays(90),
            'description' => 'Test description.',
            'submitted_at' => now(),
        ]);
    }

    public function test_my_requests_remarks_are_collapsible_via_the_shared_details_partial(): void
    {
        $farmManager = $this->makeUser('farm_manager');
        $divisionHead = $this->makeUser('division_head');
        $request = $this->makeRequest($farmManager, 'APIS-2026-REMARKS01');

        RequestTransition::create([
            'project_request_id' => $request->id,
            'acted_by_id' => $divisionHead->id,
            'acted_by_role' => 'division_head',
            'action' => 'recommended',
            'from_status' => 'submitted',
            'to_status' => 'recommended',
            'from_step' => 'division_head_review',
            'to_step' => 'vp_gen_services_approval',
            'from_owner_role' => 'division_head',
            'to_owner_role' => 'vp_gen_services',
            'to_owner_id' => null,
            'is_rework' => false,
            'is_exception_path' => false,
            'is_terminal' => false,
            'remarks' => 'Looks good, forwarding to VP.',
            'context' => [],
            'acted_at' => now()->setTime(14, 5),
        ]);

        $this->actingAs($farmManager);

        $html = Livewire::test(MyRequestsPage::class)->html();

        // The shared remarks-section partial renders a native <details> element with a
        // "Previous remarks (N)" summary -- that's the collapsible behavior. The old
        // hand-rolled block never had this; it just dumped the remark permanently open.
        $this->assertStringContainsString('<details', $html);
        $this->assertStringContainsString('Previous remarks (1)', $html);
        $this->assertStringContainsString('Looks good, forwarding to VP.', $html);

        // Each remark shows its own timestamp (the date this specific action
        // happened), not one date for the whole section.
        $this->assertStringContainsString(now()->setTime(14, 5)->format('Y-m-d h:i A'), $html);
    }

    public function test_each_remark_shows_its_own_individual_date_not_one_shared_date(): void
    {
        $farmManager = $this->makeUser('farm_manager');
        $divisionHead = $this->makeUser('division_head');
        $vp = $this->makeUser('vp_gen_services');
        $request = $this->makeRequest($farmManager, 'APIS-2026-REMARKS02');

        $recommendedAt = now()->subDays(3)->setTime(9, 0);
        $approvedAt = now()->subDay()->setTime(16, 30);

        RequestTransition::create([
            'project_request_id' => $request->id,
            'acted_by_id' => $divisionHead->id,
            'acted_by_role' => 'division_head',
            'action' => 'recommended',
            'from_status' => 'submitted',
            'to_status' => 'recommended',
            'from_step' => 'division_head_review',
            'to_step' => 'vp_gen_services_approval',
            'from_owner_role' => 'division_head',
            'to_owner_role' => 'vp_gen_services',
            'to_owner_id' => null,
            'is_rework' => false,
            'is_exception_path' => false,
            'is_terminal' => false,
            'remarks' => 'Recommended for approval by Division Head.',
            'context' => [],
            'acted_at' => $recommendedAt,
        ]);

        RequestTransition::create([
            'project_request_id' => $request->id,
            'acted_by_id' => $vp->id,
            'acted_by_role' => 'vp_gen_services',
            'action' => 'approved',
            'from_status' => 'recommended',
            'to_status' => 'vp_approved',
            'from_step' => 'vp_gen_services_approval',
            'to_step' => 'ed_manager_acceptance',
            'from_owner_role' => 'vp_gen_services',
            'to_owner_role' => 'ed_manager',
            'to_owner_id' => null,
            'is_rework' => false,
            'is_exception_path' => false,
            'is_terminal' => false,
            'remarks' => 'Approved by VP Gen Services.',
            'context' => [],
            'acted_at' => $approvedAt,
        ]);

        $this->actingAs($farmManager);

        $html = Livewire::test(MyRequestsPage::class)->html();

        $this->assertStringContainsString('Previous remarks (2)', $html);
        $this->assertStringContainsString($recommendedAt->format('Y-m-d h:i A'), $html);
        $this->assertStringContainsString($approvedAt->format('Y-m-d h:i A'), $html);
    }

    public function test_farm_manager_can_access_request_summary_and_sees_only_their_own_requests(): void
    {
        $farmManagerA = $this->makeUser('farm_manager');
        $farmManagerB = $this->makeUser('farm_manager');

        $ownRequest = $this->makeRequest($farmManagerA, 'APIS-2026-MINE01');
        $othersRequest = $this->makeRequest($farmManagerB, 'APIS-2026-OTHER01');

        $this->actingAs($farmManagerA);

        $this->get(route('farm-manager.request-summary'))->assertOk();

        $rows = Livewire::test(RequestSummaryPage::class)->get('rows');
        $ids = collect($rows)->pluck('id')->all();

        $this->assertContains('APIS-2026-MINE01', $ids);
        $this->assertNotContains('APIS-2026-OTHER01', $ids, 'Farm Manager must not see another Farm Manager\'s requests in their summary.');
    }
}
