<?php

namespace Tests\Feature;

use App\Livewire\DHGenServices\NotingPage as DhNotingPage;
use App\Livewire\DivisionHead\InboxPage as DivisionHeadInboxPage;
use App\Livewire\Engineer\InboxPage as EngineerInboxPage;
use App\Livewire\EDManager\InboxPage as EdManagerInboxPage;
use App\Livewire\FarmManager\MyRequestsPage;
use App\Livewire\VPGenServices\InboxPage as VpInboxPage;
use App\Models\ProjectRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DetailFieldsRenderTest extends TestCase
{
    use RefreshDatabase;

    protected function makeUser(string $role): User
    {
        return User::factory()->create(['role' => $role, 'is_active' => true]);
    }

    protected function makeRequest(User $requestor, string $ownerRole): ProjectRequest
    {
        return ProjectRequest::create([
            'request_number' => 'APIS-2026-TEST',
            'requestor_id' => $requestor->id,
            'requestor_role' => 'farm_manager',
            'current_status' => 'submitted',
            'current_step' => 'exception_review',
            'current_owner_role' => $ownerRole,
            'current_owner_id' => null,
            'is_late' => true,
            'is_exception_flow' => true,
            'title' => 'Render Test Project',
            'request_type' => 'Production Building',
            'budget_category' => 'small',
            'farm_name' => 'Test Farm',
            'purpose' => 'Testing detail fields rendering',
            'date_needed' => now()->addDays(90),
            'project_start_date' => now()->addDays(30),
            'project_completion_date' => now()->addDays(75),
            'description' => 'A request used purely to render the new detail-field and approval-chain partials.',
            'submitted_at' => now(),
            'meta' => [
                'jl' => [
                    'delayReason' => 'Permit delays',
                    'estimatedTurnoverDate' => now()->addDays(120)->toDateString(),
                    'implicationIfNotCompleted' => 'Production capacity loss',
                    'estimatedFinancialOpportunityLoss' => 'PHP 50,000',
                ],
            ],
        ]);
    }

    public function test_all_role_inboxes_render_new_detail_and_chain_partials(): void
    {
        $farmManager = $this->makeUser('farm_manager');
        $divisionHead = $this->makeUser('division_head');
        $vp = $this->makeUser('vp_gen_services');
        $ed = $this->makeUser('ed_manager');
        $dh = $this->makeUser('dh_gen_services');
        $engineer = $this->makeUser('engineer');

        $request = $this->makeRequest($farmManager, 'division_head');

        $this->actingAs($farmManager);
        Livewire::test(MyRequestsPage::class)->assertOk();

        $this->actingAs($divisionHead);
        Livewire::test(DivisionHeadInboxPage::class)
            ->assertOk()
            ->assertSee($request->request_number)
            ->assertSee('Permit delays')
            ->call('toggleRequest', $request->request_number);

        $request->update(['current_owner_role' => 'vp_gen_services']);
        $this->actingAs($vp);
        Livewire::test(VpInboxPage::class)
            ->assertOk()
            ->assertSee($request->request_number)
            ->assertSee('Permit delays');

        $request->update(['current_owner_role' => 'ed_manager']);
        $this->actingAs($ed);
        Livewire::test(EdManagerInboxPage::class)
            ->assertOk()
            ->assertSee($request->request_number)
            ->assertSee('Permit delays');

        $request->update(['current_owner_role' => 'dh_gen_services']);
        $this->actingAs($dh);
        Livewire::test(DhNotingPage::class)
            ->assertOk()
            ->assertSee($request->request_number)
            ->assertSee('Permit delays');

        $request->update(['current_owner_role' => 'engineer', 'current_owner_id' => $engineer->id]);
        $this->actingAs($engineer);
        Livewire::test(EngineerInboxPage::class)
            ->assertOk()
            ->assertSee($request->request_number)
            ->assertSee('Permit delays');
    }
}
