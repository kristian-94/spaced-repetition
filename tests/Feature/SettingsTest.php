<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\TelegramService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_settings_page_requires_auth(): void
    {
        $this->get(route('settings.show'))->assertRedirect(route('login'));
    }

    public function test_settings_page_renders(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('settings.show'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Settings/Index'));
    }

    public function test_user_can_update_telegram_chat_id(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patch(route('settings.update'), ['telegram_chat_id' => '123456789'])
            ->assertRedirect();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'telegram_chat_id' => '123456789',
        ]);
    }

    public function test_user_can_clear_telegram_chat_id(): void
    {
        $user = User::factory()->create(['telegram_chat_id' => '123456789']);

        $this->actingAs($user)
            ->patch(route('settings.update'), ['telegram_chat_id' => null])
            ->assertRedirect();

        $this->assertNull($user->fresh()->telegram_chat_id);
    }

    public function test_user_can_generate_api_token(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('settings.token.generate'), ['name' => 'Claude Script'])
            ->assertRedirect();

        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'name' => 'Claude Script',
        ]);
    }

    public function test_token_name_is_required(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('settings.token.generate'), ['name' => ''])
            ->assertSessionHasErrors('name');
    }

    public function test_user_can_revoke_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('My Token');
        $tokenId = $token->accessToken->id;

        $this->actingAs($user)
            ->delete(route('settings.token.revoke', $tokenId))
            ->assertRedirect();

        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $tokenId]);
    }

    public function test_test_notification_requires_telegram_chat_id(): void
    {
        $user = User::factory()->create(['telegram_chat_id' => null]);

        $this->actingAs($user)
            ->post(route('settings.test-notification'))
            ->assertSessionHasErrors('telegram');
    }

    public function test_test_notification_sends_telegram_message(): void
    {
        $user = User::factory()->create(['telegram_chat_id' => '123456789']);

        $mock = Mockery::mock(TelegramService::class);
        $mock->shouldReceive('send')
            ->once()
            ->with(Mockery::on(fn ($u) => $u->id === $user->id), Mockery::type('string'));

        $this->app->instance(TelegramService::class, $mock);

        $this->actingAs($user)
            ->post(route('settings.test-notification'))
            ->assertRedirect();
    }
}
