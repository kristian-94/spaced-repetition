<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocsTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_docs_page_is_public(): void
    {
        $this->withoutVite()
            ->get(route('docs.api'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Docs/Api')
                ->has('baseUrl')
            );
    }

    public function test_api_docs_page_renders_when_authed(): void
    {
        $user = User::factory()->create();

        $this->withoutVite()
            ->actingAs($user)
            ->get(route('docs.api'))
            ->assertOk();
    }
}
