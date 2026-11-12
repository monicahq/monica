<?php

declare(strict_types=1);

namespace Tests\Feature\Views\Layouts;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MarketplaceLayoutTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_offers_a_signed_in_person_the_way_out(): void
    {
        $user = User::factory()->create(['first_name' => 'Pam', 'last_name' => 'Beesly']);

        $this->actingAs($user)
            ->blade('<x-marketplace-layout>Anything</x-marketplace-layout>')
            ->assertSee(route('auth.login.destroy'), escape: false);
    }

    #[Test]
    public function it_offers_a_visitor_no_way_out(): void
    {
        $this->blade('<x-marketplace-layout>Anything</x-marketplace-layout>')
            ->assertDontSee(route('auth.login.destroy'), escape: false);
    }

    #[Test]
    public function it_offers_a_visitor_an_account_while_registration_is_enabled(): void
    {
        config()->set('monica.registration_enabled', true);

        $this->blade('<x-marketplace-layout>Anything</x-marketplace-layout>')
            ->assertSee(route('auth.register.new'), escape: false);
    }

    #[Test]
    public function it_offers_a_visitor_no_account_while_registration_is_disabled(): void
    {
        config()->set('monica.registration_enabled', false);

        $this->blade('<x-marketplace-layout>Anything</x-marketplace-layout>')
            ->assertDontSee(route('auth.register.new'), escape: false);
    }
}
