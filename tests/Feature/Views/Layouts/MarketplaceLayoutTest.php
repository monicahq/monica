<?php

declare(strict_types=1);

namespace Tests\Feature\Views\Layouts;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MarketplaceLayoutTest extends TestCase
{
    #[Test]
    public function it_offers_a_visitor_an_account_while_registration_is_enabled(): void
    {
        config()->set('monica.registration_enabled', true);

        $this->blade('<x-marketplace-layout>Anything</x-marketplace-layout>')
            ->assertSee(route('auth.register.new'), escape: false)
            ->assertSee('Sign in');
    }

    #[Test]
    public function it_offers_a_visitor_no_account_while_registration_is_disabled(): void
    {
        config()->set('monica.registration_enabled', false);

        $this->blade('<x-marketplace-layout>Anything</x-marketplace-layout>')
            ->assertDontSee(route('auth.register.new'), escape: false)
            ->assertSee('Sign in');
    }
}
