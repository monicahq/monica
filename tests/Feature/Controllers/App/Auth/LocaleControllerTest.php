<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Auth;

use App\Helpers\LocaleResolution;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LocaleControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_changes_the_language_of_the_interface(): void
    {
        $response = $this->from(route('auth.register.new'))
            ->put(route('auth.locale.update'), ['locale' => 'fr_FR']);

        $response->assertRedirect(route('auth.register.new'));
        $response->assertSessionHas('locale', 'fr_FR');
        $response->assertCookie(LocaleResolution::COOKIE_NAME, 'fr_FR');

        $this->get(route('auth.register.new'))->assertSee('Créez votre compte');
    }

    #[Test]
    public function the_language_it_keeps_on_the_device_outlives_the_session_it_was_chosen_in(): void
    {
        $response = $this->put(route('auth.locale.update'), ['locale' => 'it_IT']);

        $cookie = $response->getCookie(LocaleResolution::COOKIE_NAME);

        $this->assertNotNull($cookie);
        $this->assertGreaterThan(
            now()->addMonths(11)->getTimestamp(),
            $cookie->getExpiresTime(),
        );
    }

    #[Test]
    public function it_refuses_a_language_the_interface_does_not_speak(): void
    {
        $response = $this->put(route('auth.locale.update'), ['locale' => 'sv_SE']);

        $response->assertSessionHasErrors('locale');
        $this->assertNull(session('locale'));
        $response->assertCookieMissing(LocaleResolution::COOKIE_NAME);
    }
}
