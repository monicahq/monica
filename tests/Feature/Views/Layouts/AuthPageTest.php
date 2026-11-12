<?php

declare(strict_types=1);

namespace Tests\Feature\Views\Layouts;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AuthPageTest extends TestCase
{
    #[Test]
    public function it_keeps_turbo_from_showing_a_stale_copy_or_fetching_on_hover(): void
    {
        $this->blade('<x-auth-page title="Welcome back">The card</x-auth-page>')
            ->assertSee('<meta name="turbo-cache-control" content="no-cache" />', escape: false)
            ->assertSee('<meta name="turbo-prefetch" content="false" />', escape: false)
            ->assertSee('data-turbo-track="reload"', escape: false);
    }

    #[Test]
    public function it_is_loaded_in_full_when_it_carries_the_turnstile_widget(): void
    {
        config(['turnstile.enabled' => false]);

        $this->blade('<x-auth-page title="Welcome back">The card</x-auth-page>')
            ->assertDontSee('turbo-visit-control', escape: false);

        config(['turnstile.enabled' => true]);

        $this->blade('<x-auth-page title="Welcome back">The card</x-auth-page>')
            ->assertSee('<meta name="turbo-visit-control" content="reload" />', escape: false);
    }

    #[Test]
    public function it_names_the_screen_once_in_the_title_and_once_on_it(): void
    {
        $this->blade('<x-auth-page title="Welcome back">The card</x-auth-page>')
            ->assertSee('<title>Welcome back</title>', escape: false)
            ->assertSee('Welcome back')
            ->assertSee('The card');
    }
}
