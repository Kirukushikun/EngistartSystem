<?php
namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SidebarEdManagerVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_ed_manager_sidebar_shows_assigned_engineers_link(): void
    {
        $ed = User::factory()->create(['role' => 'ed_manager', 'is_active' => true]);
        $this->actingAs($ed);

        $response = $this->get(route('ed-manager.inbox'));

        $response->assertOk();
        $response->assertSee('Assigned Engineers');
        $response->assertSee(route('ed-manager.assigned-engineers'), false);
    }
}
