<?php

namespace Tests\Feature;

use App\Livewire\HistoryPage;
use App\Livewire\ITAdmin\AllRequestsPage;
use App\Livewire\ITAdmin\AuditTrailPage;
use App\Livewire\ITAdmin\StatusOverridePage;
use App\Livewire\ITAdmin\UsersPage;
use App\Livewire\Shared\RequestSummaryPage;
use App\Livewire\VPGenServices\ChangeRequestsPage;
use App\Models\ProjectRequest;
use App\Models\RequestTransition;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PaginationRefactorRenderTest extends TestCase
{
    use RefreshDatabase;

    protected function makeUser(string $role): User
    {
        return User::factory()->create(['role' => $role, 'is_active' => true]);
    }

    public function test_pages_using_the_shared_pagination_trait_render_and_paginate_correctly(): void
    {
        $farmManager = $this->makeUser('farm_manager');
        $itAdmin = $this->makeUser('it_admin');
        $vp = $this->makeUser('vp_gen_services');

        // Seed enough rows to force pagination math to actually kick in (perPage defaults are small).
        for ($i = 0; $i < 12; $i++) {
            $request = ProjectRequest::create([
                'request_number' => 'APIS-2026-PG' . str_pad((string) $i, 3, '0', STR_PAD_LEFT),
                'requestor_id' => $farmManager->id,
                'requestor_role' => 'farm_manager',
                'current_status' => 'submitted',
                'current_step' => 'division_head_review',
                'current_owner_role' => 'division_head',
                'current_owner_id' => null,
                'is_late' => false,
                'is_exception_flow' => false,
                'title' => 'Pagination Test Project ' . $i,
                'request_type' => 'Production Building',
                'budget_category' => 'small',
                'farm_name' => 'Test Farm',
                'purpose' => 'Testing pagination trait',
                'date_needed' => now()->addDays(90),
                'description' => 'Pagination smoke test row.',
                'submitted_at' => now()->subDays($i),
            ]);

            RequestTransition::create([
                'project_request_id' => $request->id,
                'acted_by_id' => $farmManager->id,
                'acted_by_role' => 'farm_manager',
                'action' => 'submitted',
                'from_status' => null,
                'to_status' => 'submitted',
                'from_step' => null,
                'to_step' => 'division_head_review',
                'from_owner_role' => null,
                'to_owner_role' => 'division_head',
                'to_owner_id' => null,
                'is_rework' => false,
                'is_exception_path' => false,
                'is_terminal' => false,
                'remarks' => 'Submitted.',
                'context' => [],
                'acted_at' => now()->subDays($i),
            ]);
        }

        $this->actingAs($itAdmin);

        Livewire::test(AllRequestsPage::class)
            ->assertOk()
            ->assertSet('page', 1)
            ->call('nextPage')
            ->assertSet('page', 2)
            ->call('previousPage')
            ->assertSet('page', 1);

        Livewire::test(AuditTrailPage::class)->assertOk();
        Livewire::test(StatusOverridePage::class)->assertOk();
        Livewire::test(UsersPage::class)->assertOk();

        $this->actingAs($vp);

        Livewire::test(HistoryPage::class)->assertOk();
        Livewire::test(RequestSummaryPage::class)
            ->assertOk()
            ->call('nextPage')
            ->call('previousPage');
        Livewire::test(ChangeRequestsPage::class)->assertOk();
    }
}
