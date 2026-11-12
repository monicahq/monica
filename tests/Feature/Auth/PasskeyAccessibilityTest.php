<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PasskeyAccessibilityTest extends TestCase
{
    use RefreshDatabase;

    private function secure(): self
    {
        URL::forceScheme('https');

        return $this;
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
}
