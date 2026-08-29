<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Models\WebauthnKey;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The parts of WCAG 2.2 AA that can be held to by looking at the markup. The
 * rest of the pass, keyboard order, contrast, zoom and phone width, is done by
 * hand and cannot be asserted here.
 *
 * The live region is the one worth a test of its own. A cancelled device prompt
 * changes nothing visible on the page, so announcing it is the only way somebody
 * using a screen reader learns that nothing happened.
 */
class PasskeyAccessibilityTest extends TestCase
{
    use RefreshDatabase;

    private function secure(): self
    {
        URL::forceScheme('https');

        return $this;
    }

    #[Test]
    public function the_sign_in_screen_carries_a_live_region_that_is_always_present(): void
    {
        $response = $this->secure()->get(route('auth.login.new'));

        $response->assertSee('role="status"', escape: false);
        $response->assertSee('aria-live="polite"', escape: false);
        $response->assertDontSee('x-show="message"', escape: false);
    }

    #[Test]
    public function the_security_screen_carries_a_live_region_that_is_always_present(): void
    {
        $response = $this->secure()->actingAs(User::factory()->create())
            ->get(route('settings.security.index'));

        $response->assertSee('role="status"', escape: false);
        $response->assertSee('aria-live="polite"', escape: false);
        $response->assertDontSee('x-show="message"', escape: false);
    }

    #[Test]
    public function every_passkey_control_is_a_real_button(): void
    {
        $user = User::factory()->create();
        WebauthnKey::factory()->create(['user_id' => $user->id]);

        $html = $this->secure()->actingAs($user)->get(route('settings.security.index'))->getContent();

        foreach (['Add a passkey', 'Rename', 'Remove'] as $label) {
            $this->assertMatchesRegularExpression(
                '/<button[^>]*>(?:(?!<\/button>).)*'.preg_quote($label, '/').'/s',
                $html,
                $label.' is not inside a button element',
            );
        }
    }

    #[Test]
    public function the_name_field_is_labelled(): void
    {
        $html = $this->secure()->actingAs(User::factory()->create())
            ->get(route('settings.security.index'))->getContent();

        $this->assertStringContainsString('for="passkey_name"', $html);
        $this->assertStringContainsString('Name this device', $html);
    }

    #[Test]
    public function the_removal_warning_is_words_and_not_only_a_colour(): void
    {
        $user = User::factory()->create();
        WebauthnKey::factory()->create(['user_id' => $user->id]);

        $this->secure()->actingAs($user)->get(route('settings.security.index'))
            ->assertSee('This device will no longer sign you in. Sure?');
    }

    #[Test]
    public function the_last_passkey_of_a_passwordless_account_says_what_it_costs(): void
    {
        $user = User::factory()->create(['password' => null]);
        WebauthnKey::factory()->create(['user_id' => $user->id]);

        $this->secure()->actingAs($user)->get(route('settings.security.index'))
            ->assertSee('This is your last passkey and you have no password.', escape: false);
    }
}
