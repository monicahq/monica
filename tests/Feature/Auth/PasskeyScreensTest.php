<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\Passkey;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PasskeyScreensTest extends TestCase
{
    use RefreshDatabase;

    private function secure(): self
    {
        URL::forceScheme('https');

        return $this;
    }

    private function insecure(): self
    {
        URL::forceScheme('http');

        return $this;
    }

    #[Test]
    public function the_security_screen_says_why_passkeys_cannot_be_used_here(): void
    {
        $user = User::factory()->create();

        $response = $this->insecure()->actingAs($user)->get(route('settings.security.index'));

        $response->assertStatus(200);
    }

    #[Test]
    public function the_security_screen_lists_a_passkey_that_has_never_been_used(): void
    {
        $user = User::factory()->create();
        Passkey::factory()->create(['user_id' => $user->id, 'name' => 'Michael MacBook']);

        $response = $this->secure()->actingAs($user)->get(route('settings.security.index'));

        $response->assertSee('Michael MacBook');
    }
}
