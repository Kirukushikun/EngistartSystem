<?php

namespace Tests\Feature;

use App\Livewire\Shared\RequestSummaryPage;
use App\Models\ProjectRequest;
use App\Models\RequestTransition;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RequestSummaryRoleScopingTest extends TestCase
{
    use RefreshDatabase;

    protected function makeUser(string $role): User
    {
        return User::factory()->create(['role' => $role, 'is_active' => true]);
    }

    protected function makeRequest(User $requestor, string $requestNumber, string $ownerRole): ProjectRequest
    {
        return ProjectRequest::create([
            'request_number' => $requestNumber,
            'requestor_id' => $requestor->id,
            'requestor_role' => 'farm_manager',
            'current_status' => 'submitted',
            'current_step' => 'division_head_review',
            'current_owner_role' => $ownerRole,
            'current_owner_id' => null,
            'is_late' => false,
            'is_exception_flow' => false,
            'title' => 'Role Scoping Test ' . $requestNumber,
            'request_type' => 'Production Building',
            'budget_category' => 'small',
            'farm_name' => 'Scoping Test Farm',
            'purpose' => 'Testing role-scoped summary',
            'date_needed' => now()->addDays(90),
            'description' => 'Role scoping test.',
            'submitted_at' => now(),
        ]);
    }

    public function test_summary_only_shows_requests_currently_waiting_on_or_previously_acted_on_by_that_role(): void
    {
        $farmManager = $this->makeUser('farm_manager');
        $divisionHead = $this->makeUser('division_head');
        $vp = $this->makeUser('vp_gen_services');

        // Currently waiting on Division Head -- VP has never touched it.
        $waitingOnDh = $this->makeRequest($farmManager, 'APIS-2026-SCOPE01', 'division_head');

        // Division Head already recommended this one; it's now waiting on VP.
        $alreadyHandledByDh = $this->makeRequest($farmManager, 'APIS-2026-SCOPE02', 'vp_gen_services');
        RequestTransition::create([
            'project_request_id' => $alreadyHandledByDh->id,
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
            'remarks' => 'Recommended.',
            'context' => [],
            'acted_at' => now(),
        ]);

        // Never touched by Division Head at all -- straight to VP with no DH transition.
        $neverTouchedByDh = $this->makeRequest($farmManager, 'APIS-2026-SCOPE03', 'vp_gen_services');

        // Division Head sees: the one waiting on them, and the one they already acted on.
        // They must NOT see the one that skipped them entirely.
        $this->actingAs($divisionHead);
        $dhRows = Livewire::test(RequestSummaryPage::class)->get('rows');
        $dhIds = collect($dhRows)->pluck('id')->all();

        $this->assertContains('APIS-2026-SCOPE01', $dhIds);
        $this->assertContains('APIS-2026-SCOPE02', $dhIds);
        $this->assertNotContains('APIS-2026-SCOPE03', $dhIds);

        // VP sees: the one currently waiting on them (SCOPE02) and the one that came straight
        // to them (SCOPE03). They must NOT see the one still sitting with Division Head (SCOPE01),
        // since VP has never been involved with it.
        $this->actingAs($vp);
        $vpRows = Livewire::test(RequestSummaryPage::class)->get('rows');
        $vpIds = collect($vpRows)->pluck('id')->all();

        $this->assertNotContains('APIS-2026-SCOPE01', $vpIds);
        $this->assertContains('APIS-2026-SCOPE02', $vpIds);
        $this->assertContains('APIS-2026-SCOPE03', $vpIds);
    }
}
