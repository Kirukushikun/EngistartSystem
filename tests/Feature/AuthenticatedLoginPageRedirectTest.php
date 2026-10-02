<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Laravel's built-in `guest` middleware bounces an already-authenticated
 * visitor away from /login by redirecting to a route named `dashboard` or
 * `home`, falling back to `/` if neither exists. This app has neither a
 * `dashboard` route nor (until now) a `home` route -- and `/` unconditionally
 * redirects to `/login` -- so an authenticated user visiting /login fell into
 * an infinite /login <-> / loop (ERR_TOO_MANY_REDIRECTS). The `home` route
 * added to routes/web.php is the fix; these tests pin that behavior.
 */
class AuthenticatedLoginPageRedirectTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_authenticated_user_visiting_login_is_sent_to_home_not_to_the_root_loop(): void
    {
        $user = User::factory()->create(['role' => 'division_head', 'is_active' => true]);

        $this->actingAs($user)
            ->get('/login')
            ->assertRedirect(route('home'));
    }

    public function test_the_home_route_forwards_each_role_to_its_own_page(): void
    {
        $divisionHead = User::factory()->create(['role' => 'division_head', 'is_active' => true]);
        $this->actingAs($divisionHead)->get('/home')->assertRedirect(route('division-head.inbox'));

        $itAdmin = User::factory()->create(['role' => 'it_admin', 'is_active' => true]);
        $this->actingAs($itAdmin)->get('/home')->assertRedirect(route('it-admin.all-requests'));
    }

    public function test_the_full_authenticated_redirect_chain_terminates_without_looping(): void
    {
        $user = User::factory()->create(['role' => 'it_admin', 'is_active' => true]);

        // If the loop still existed, following redirects to completion would
        // either hang or eventually fail Laravel's own redirect-following
        // safeguards rather than cleanly reach a 200.
        $this->followingRedirects()
            ->actingAs($user)
            ->get('/login')
            ->assertOk();

        $this->followingRedirects()
            ->actingAs($user)
            ->get('/')
            ->assertOk();
    }

    public function test_an_unauthenticated_visitor_is_unaffected_and_still_reaches_login(): void
    {
        $this->get('/login')->assertOk();

        $this->followingRedirects()->get('/')->assertOk();
    }
}
