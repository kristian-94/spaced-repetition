<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;
    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        // Root renders the marketing landing page for unauthenticated visitors
        $this->withoutVite()
            ->get('/')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Landing'));
    }

    public function test_root_redirects_authenticated_users_to_decks(): void
    {
        $user = \App\Models\User::factory()->create();

        $this->actingAs($user)
            ->get('/')
            ->assertRedirect(route('decks.index'));
    }
}
