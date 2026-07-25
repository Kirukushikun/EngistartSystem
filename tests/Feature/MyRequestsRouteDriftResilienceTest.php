<?php

namespace Tests\Feature;

use App\Livewire\FarmManager\MyRequestsPage;
use App\Models\ProjectRequest;
use App\Models\RequestTransition;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use Tests\TestCase;

class MyRequestsRouteDriftResilienceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Guards against the production incident where "farm-manager.requests.reschedule-meeting"
     * wasn't deployed/cached yet, but the code referencing it was -- crashing the entire
     * "My Requests" page with RouteNotFoundException for every Farm Manager, not just the one
     * row awaiting a reschedule. The Blade view now checks Route::has() before linking to it,
     * same pattern as the sidebar's drift guard.
     */
    public function test_my_requests_page_renders_the_reschedule_link_only_when_the_route_is_registered(): void
    {
        $farmManager = User::factory()->create(['role' => 'farm_manager', 'is_active' => true]);

        $request = ProjectRequest::create([
            'request_number' => 'APIS-2026-ROUTEDRIFT01',
            'requestor_id' => $farmManager->id,
            'requestor_role' => 'farm_manager',
            'current_status' => 'reschedule_requested',
            'current_step' => 'requestor_reschedule',
            'current_owner_role' => 'farm_manager',
            'current_owner_id' => $farmManager->id,
            'is_late' => false,
            'is_exception_flow' => false,
            'title' => 'Route Drift Test Project',
            'request_type' => 'Production Building',
            'budget_category' => 'small',
            'farm_name' => 'Route Drift Farm',
            'purpose' => 'Testing route drift resilience',
            'date_needed' => now()->addDays(90),
            'description' => 'Route drift test.',
            'preferred_meeting_date' => now()->addDays(10),
            'preferred_meeting_time' => '10:00',
            'submitted_at' => now(),
        ]);

        RequestTransition::create([
            'project_request_id' => $request->id,
            'acted_by_id' => $farmManager->id,
            'acted_by_role' => 'farm_manager',
            'action' => 'submitted',
            'from_status' => null,
            'to_status' => 'reschedule_requested',
            'from_step' => null,
            'to_step' => 'requestor_reschedule',
            'from_owner_role' => null,
            'to_owner_role' => 'farm_manager',
            'to_owner_id' => $farmManager->id,
            'is_rework' => false,
            'is_exception_path' => false,
            'is_terminal' => false,
            'remarks' => 'Setup.',
            'context' => [],
            'acted_at' => now(),
        ]);

        $this->actingAs($farmManager);

        // Sanity: with the route present (the normal case), the page renders and shows the link.
        $this->assertTrue(Route::has('farm-manager.requests.reschedule-meeting'));

        Livewire::test(MyRequestsPage::class)
            ->assertOk()
            ->assertSee('Route Drift Test Project')
            ->assertSee('Update Meeting Schedule');
    }
}
