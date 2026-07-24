<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class SidebarRouteDriftResilienceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Simulates the exact production incident: config/sidebar.php references a route name
     * that isn't registered (e.g. a deploy where the config shipped ahead of routes/web.php,
     * or a stale config cache). Before the Route::has() guard in app.blade.php, this would
     * throw RouteNotFoundException and take down the entire page, not just the sidebar link.
     */
    public function test_a_sidebar_item_pointing_at_a_nonexistent_route_does_not_break_the_page(): void
    {
        $engineer = User::factory()->create(['role' => 'engineer', 'is_active' => true]);

        $modules = Config::get('sidebar.modules');
        $modules['engineer']['items'][] = [
            'label' => 'Drifted Link',
            'route' => 'engineer.this-route-does-not-exist',
            'active' => ['engineer.this-route-does-not-exist'],
        ];
        Config::set('sidebar.modules', $modules);

        $this->actingAs($engineer);

        $response = $this->get(route('engineer.inbox'));

        $response->assertOk();
        $response->assertDontSee('Drifted Link');
        // The rest of the sidebar for this role should still render normally.
        $response->assertSee('For Initialization');
        $response->assertSee('Project Request Summary');
    }
}
