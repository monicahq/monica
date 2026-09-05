<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Settings\Account\Security;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ApiKeyControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_creates_an_api_key(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('settings.apiKeys.create'), [
            'name' => 'Dundie awards bot',
        ]);

        $response->assertRedirect(route('settings.security.index'));
        $response->assertSessionHas('status', 'Your API key is ready.');

        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'name' => 'Dundie awards bot',
        ]);
    }

    #[Test]
    public function it_hands_the_key_over_once(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('settings.apiKeys.create'), [
            'name' => 'Dundie awards bot',
        ]);

        $response->assertSessionHas('apiKey');

        $this->actingAs($user)->get(route('settings.security.index'))
            ->assertStatus(200)
            ->assertSee('Your new API key', escape: false);

        $this->actingAs($user)->get(route('settings.security.index'))
            ->assertDontSee('Your new API key', escape: false);
    }

    #[Test]
    public function it_prints_the_new_key_in_a_box_a_click_selects_whole(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->withSession(['apiKey' => 'dunder-mifflin-infinity'])
            ->get(route('settings.security.index'))
            ->assertStatus(200)
            ->assertSee('select-all">dunder-mifflin-infinity</p>', escape: false);
    }

    #[Test]
    public function it_has_somewhere_to_announce_what_a_copy_did(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->withSession(['apiKey' => 'dunder-mifflin-infinity'])
            ->get(route('settings.security.index'))
            ->assertSee('aria-live="polite"', escape: false)
            ->assertSee('x-text="messages[state]', escape: false);
    }

    #[Test]
    public function it_offers_to_copy_the_new_key_and_says_when_it_did(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->withSession(['apiKey' => 'dunder-mifflin-infinity'])
            ->get(route('settings.security.index'))
            ->assertSee('Copy to the clipboard', escape: false)
            ->assertSee('window.copyText($refs.key.textContent)', escape: false)
            ->assertSee("copied: 'Copied'", escape: false)
            ->assertSee('select-all">dunder-mifflin-infinity</p>', escape: false);
    }

    #[Test]
    public function it_says_when_the_copy_did_not_happen(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->withSession(['apiKey' => 'dunder-mifflin-infinity'])
            ->get(route('settings.security.index'))
            ->assertSee("failed: 'We could not copy it. Select the key and copy it by hand.'", escape: false)
            ->assertSee("state = ok ? 'copied' : 'failed'", escape: false);
    }

    #[Test]
    public function it_refuses_a_name_that_is_too_short(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('settings.apiKeys.create'), [
            'name' => 'ab',
        ]);

        $response->assertSessionHasErrors('name');

        $this->assertSame(0, $user->tokens()->count());
    }

    #[Test]
    public function it_refuses_a_missing_name(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('settings.apiKeys.create'), []);

        $response->assertSessionHasErrors('name');
    }

    #[Test]
    public function it_revokes_an_api_key(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $apiKey = $user->createToken('Dundie awards bot')->accessToken;

        $response = $this->actingAs($user)->delete(route('settings.apiKeys.destroy', $apiKey->id));

        $response->assertRedirect(route('settings.security.index'));
        $response->assertSessionHas('status', 'The API key is revoked.');

        $this->assertModelMissing($apiKey);
    }

    #[Test]
    public function it_refuses_to_revoke_a_key_that_belongs_to_somebody_else(): void
    {
        Queue::fake();

        $michael = User::factory()->create();
        $dwight = User::factory()->create();
        $apiKey = $dwight->createToken('Beet farm sync')->accessToken;

        $response = $this->actingAs($michael)->delete(route('settings.apiKeys.destroy', $apiKey->id));

        $response->assertNotFound();

        $this->assertModelExists($apiKey);
    }

    #[Test]
    public function it_refuses_a_visitor_who_is_not_signed_in(): void
    {
        $this->post(route('settings.apiKeys.create'), ['name' => 'Dundie awards bot'])
            ->assertRedirect(route('auth.login.new'));

        $this->delete(route('settings.apiKeys.destroy', 1))
            ->assertRedirect(route('auth.login.new'));
    }
}
