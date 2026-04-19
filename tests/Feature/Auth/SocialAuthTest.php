<?php

namespace Tests\Feature\Auth;

use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;
use Mockery;
use Tests\TestCase;

class SocialAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_renders(): void
    {
        $this->get('/login')->assertOk();
    }

    public function test_redirect_hits_socialite_for_google(): void
    {
        $fake = Mockery::mock();
        $fake->shouldReceive('redirect')
            ->once()
            ->andReturn(redirect('https://accounts.google.com/o/oauth2/auth'));

        Socialite::shouldReceive('driver')->with('google')->andReturn($fake);

        $this->get(route('auth.redirect', ['provider' => 'google']))
            ->assertRedirect('https://accounts.google.com/o/oauth2/auth');
    }

    public function test_unknown_provider_returns_404(): void
    {
        $this->get('/auth/github/redirect')->assertNotFound();
        $this->get('/auth/github/callback')->assertNotFound();
    }

    public function test_callback_creates_new_user_and_social_account(): void
    {
        $this->mockSocialiteUser([
            'id' => 'google-123',
            'email' => 'new@example.com',
            'name' => 'New Person',
            'avatar' => 'https://example.com/a.png',
        ]);

        $this->get(route('auth.callback', ['provider' => 'google']))
            ->assertRedirect(route('dashboard', absolute: false));

        $user = User::where('email', 'new@example.com')->firstOrFail();
        $this->assertSame('New Person', $user->name);
        $this->assertSame('https://example.com/a.png', $user->avatar_url);
        $this->assertNotNull($user->email_verified_at);
        $this->assertNull($user->password);

        $this->assertDatabaseHas('social_accounts', [
            'user_id' => $user->id,
            'provider' => 'google',
            'provider_id' => 'google-123',
        ]);

        $this->assertAuthenticatedAs($user);
    }

    public function test_callback_links_existing_user_by_email(): void
    {
        $existing = User::factory()->create([
            'email' => 'existing@example.com',
            'name' => 'Already Here',
        ]);

        $this->mockSocialiteUser([
            'id' => 'google-456',
            'email' => 'existing@example.com',
            'name' => 'Google Name',
            'avatar' => 'https://example.com/b.png',
        ]);

        $this->get(route('auth.callback', ['provider' => 'google']))
            ->assertRedirect(route('dashboard', absolute: false));

        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseHas('social_accounts', [
            'user_id' => $existing->id,
            'provider' => 'google',
            'provider_id' => 'google-456',
        ]);

        $existing->refresh();
        $this->assertSame('Already Here', $existing->name, 'Existing name should not be overwritten on first link.');
        $this->assertSame('https://example.com/b.png', $existing->avatar_url);

        $this->assertAuthenticatedAs($existing);
    }

    public function test_callback_reauthenticates_existing_social_account(): void
    {
        $user = User::factory()->create(['email' => 'old@example.com']);
        SocialAccount::create([
            'user_id' => $user->id,
            'provider' => 'google',
            'provider_id' => 'google-789',
        ]);

        $this->mockSocialiteUser([
            'id' => 'google-789',
            // Email may have changed on Google side — should not matter, we match by provider_id.
            'email' => 'new-email@example.com',
            'name' => 'Updated Name',
            'avatar' => 'https://example.com/c.png',
        ]);

        $this->get(route('auth.callback', ['provider' => 'google']))
            ->assertRedirect(route('dashboard', absolute: false));

        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('social_accounts', 1);

        $user->refresh();
        $this->assertSame('Updated Name', $user->name);
        $this->assertSame('old@example.com', $user->email, 'Email should not change on re-auth.');

        $this->assertAuthenticatedAs($user);
    }

    public function test_callback_without_email_redirects_back_with_error(): void
    {
        $this->mockSocialiteUser([
            'id' => 'google-no-email',
            'email' => null,
            'name' => 'No Email',
            'avatar' => null,
        ]);

        $this->get(route('auth.callback', ['provider' => 'google']))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('oauth');

        $this->assertDatabaseCount('users', 0);
        $this->assertGuest();
    }

    public function test_oauth_redirect_is_rate_limited(): void
    {
        $fake = Mockery::mock();
        $fake->shouldReceive('redirect')
            ->andReturn(redirect('https://accounts.google.com/o/oauth2/auth'));

        Socialite::shouldReceive('driver')->with('google')->andReturn($fake);

        // Throttle is 20/min per IP. First 20 requests pass, 21st should be blocked.
        for ($i = 0; $i < 20; $i++) {
            $this->get(route('auth.redirect', ['provider' => 'google']))
                ->assertStatus(302);
        }

        $this->get(route('auth.redirect', ['provider' => 'google']))
            ->assertStatus(429);
    }

    public function test_logout_destroys_session(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('logout'))
            ->assertRedirect('/');

        $this->assertGuest();
    }

    private function mockSocialiteUser(array $attrs): void
    {
        $abstractUser = Mockery::mock(SocialiteUser::class);
        $abstractUser->shouldReceive('getId')->andReturn($attrs['id']);
        $abstractUser->shouldReceive('getEmail')->andReturn($attrs['email']);
        $abstractUser->shouldReceive('getName')->andReturn($attrs['name']);
        $abstractUser->shouldReceive('getAvatar')->andReturn($attrs['avatar']);

        $driver = Mockery::mock();
        $driver->shouldReceive('user')->andReturn($abstractUser);

        Socialite::shouldReceive('driver')->with('google')->andReturn($driver);
    }
}
