<?php

namespace Tests\Feature;

use Tests\TestCase;

class RootRedirectCacheTest extends TestCase
{
    public function test_root_redirect_to_login_is_never_cached_by_the_browser(): void
    {
        $response = $this->get('/');

        $response->assertRedirect('/login');
        $cacheControl = $response->headers->get('Cache-Control');
        $this->assertStringContainsString('no-store', $cacheControl);
        $this->assertStringContainsString('no-cache', $cacheControl);
        $response->assertHeader('Pragma', 'no-cache');
    }
}
