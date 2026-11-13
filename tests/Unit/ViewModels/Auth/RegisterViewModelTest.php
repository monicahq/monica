<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Auth;

use App\Helpers\AvailableLocales;
use App\Helpers\LocaleCollation;
use App\ViewModels\Auth\RegisterViewModel;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RegisterViewModelTest extends TestCase
{
    #[Test]
    public function it_gives_the_languages_the_interface_speaks(): void
    {
        $locales = new RegisterViewModel()->locales();

        $this->assertCount(count(AvailableLocales::codes()), $locales);
        $this->assertEquals([
            'code' => 'en',
            'label' => 'English',
            'region' => 'United Kingdom',
            'flag' => config('localization.locales.en.flag'),
        ], collect($locales)->firstWhere('code', 'en'));
    }

    #[Test]
    public function it_sorts_the_languages_by_the_name_each_one_calls_itself(): void
    {
        $labels = collect(new RegisterViewModel()->locales())->pluck('label');

        $this->assertEquals(
            LocaleCollation::sortByCollator($labels, fn (string $label): string => $label)->all(),
            $labels->all(),
        );
    }

    #[Test]
    public function it_opens_on_the_password_method_over_a_secure_connection(): void
    {
        $this->app['request']->server->set('HTTPS', 'on');

        $this->assertEquals('password', new RegisterViewModel()->registrationMethod());
    }

    #[Test]
    public function it_keeps_the_passkey_method_after_a_refused_attempt(): void
    {
        $this->app['request']->server->set('HTTPS', 'on');
        $session = $this->app['session.store'];
        $session->flashInput(['registration_method' => 'passkey']);
        request()->setLaravelSession($session);

        $this->assertEquals('passkey', new RegisterViewModel()->registrationMethod());
    }

    #[Test]
    public function it_answers_the_password_method_over_a_connection_that_is_not_secure(): void
    {
        $this->app['request']->server->set('HTTPS', 'off');
        $session = $this->app['session.store'];
        $session->flashInput(['registration_method' => 'passkey']);
        request()->setLaravelSession($session);

        $this->assertEquals('password', new RegisterViewModel()->registrationMethod());
    }
}
