<?php

namespace Tests\Feature;

use App\Livewire\Shared\RequestSummaryPage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SidebarOrganizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_engineer_can_access_project_request_summary(): void
    {
        $engineer = User::factory()->create(['role' => 'engineer', 'is_active' => true]);
        $this->actingAs($engineer);

        $this->get(route('engineer.request-summary'))->assertOk();

        Livewire::test(RequestSummaryPage::class)->assertOk();
    }

    public function test_engineer_sidebar_shows_project_request_summary_link(): void
    {
        $engineer = User::factory()->create(['role' => 'engineer', 'is_active' => true]);
        $this->actingAs($engineer);

        $response = $this->get(route('engineer.inbox'));

        $response->assertOk();
        $response->assertSee('Project Request Summary');
        $response->assertSee(route('engineer.request-summary'), false);
    }

    public function test_other_roles_are_not_granted_the_engineer_request_summary_route(): void
    {
        $farmManager = User::factory()->create(['role' => 'farm_manager', 'is_active' => true]);
        $this->actingAs($farmManager);

        $this->get(route('engineer.request-summary'))->assertForbidden();
    }

    /**
     * Every role module that has both a "Summary" and "History" link should list them
     * in the same relative order, so the sidebar reads consistently across roles.
     */
    public function test_summary_link_appears_before_history_link_for_every_role_that_has_both(): void
    {
        $modules = config('sidebar.modules');

        foreach ($modules as $moduleKey => $module) {
            $labels = array_column($module['items'], 'label');
            $summaryIndex = array_search('Project Request Summary', $labels, true);
            $historyIndex = array_search('History', $labels, true);

            if ($summaryIndex === false || $historyIndex === false) {
                continue;
            }

            $this->assertLessThan(
                $historyIndex,
                $summaryIndex,
                "Expected 'Project Request Summary' to appear before 'History' in the '{$moduleKey}' sidebar module."
            );
        }
    }

    /**
     * The three roles that manage engineer accounts should all use the same sidebar label
     * for that page, even though the underlying route names differ per role.
     */
    public function test_assigned_engineers_label_is_consistent_across_roles(): void
    {
        $modules = config('sidebar.modules');
        $rolesWithEngineerManagement = ['dh-gen-services', 'ed-manager', 'it-admin'];

        foreach ($rolesWithEngineerManagement as $moduleKey) {
            $labels = array_column($modules[$moduleKey]['items'], 'label');

            $this->assertContains(
                'Assigned Engineers',
                $labels,
                "Expected the '{$moduleKey}' sidebar module to have an 'Assigned Engineers' entry."
            );
        }
    }
}
