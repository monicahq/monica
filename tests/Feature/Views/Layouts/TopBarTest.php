<?php

declare(strict_types=1);

namespace Tests\Feature\Views\Layouts;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TopBarTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_bar_of_a_screen_outside_a_vault_says_nothing_about_who_is_signed_in(): void
    {
        $user = User::factory()->create(['first_name' => 'Pam', 'last_name' => 'Beesly']);

        $rendered = $this->actingAs($user)->blade('<x-account-layout>Anything</x-account-layout>');

        $rendered->assertDontSee('PB');
        $rendered->assertDontSee($user->email);
        $rendered->assertSee('Switch between the light and dark theme');
    }

    #[Test]
    public function it_says_where_you_are_in_the_breadcrumb_slot(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->blade('<x-shell>Anything<x-slot:breadcrumb><x-breadcrumb :items="[[\'label\' => \'Dunder Mifflin\']]" /></x-slot:breadcrumb></x-shell>')
            ->assertSee('Dunder Mifflin')
            ->assertSee('aria-current="page"', escape: false);
    }

    #[Test]
    public function it_leaves_who_is_signed_in_to_the_foot_of_the_navigation_column(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->blade('<x-shell>Anything</x-shell>')
            ->assertDontSee($user->email);
    }

    #[Test]
    public function it_carries_what_the_screen_can_do(): void
    {
        $user = User::factory()->create();

        $rendered = $this->actingAs($user)
            ->blade('<x-shell>Anything<x-slot:actions><span>Add somebody</span></x-slot:actions></x-shell>');

        $rendered->assertSee('Add somebody');
        $rendered->assertDontSee($user->email);
    }

    #[Test]
    public function it_opens_the_drawer_from_the_bar(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->blade('<x-shell>Anything</x-shell>')
            ->assertSee('aria-controls="main-nav"', escape: false)
            ->assertSee('aria-label="Open the menu"', escape: false);
    }

    #[Test]
    public function the_drawer_state_is_declared_inside_the_body_so_turbo_walks_it_again(): void
    {
        $user = User::factory()->create();

        $rendered = $this->actingAs($user)->blade('<x-shell>Anything</x-shell>')->__toString();

        $body = mb_substr($rendered, mb_strpos($rendered, '<body'));

        $this->assertStringContainsString('x-data="{ navOpen: false }"', $body);
    }

    #[Test]
    public function a_footer_reaches_the_screen(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->blade('<x-shell>Anything<x-slot:footer><span>Save it</span></x-slot:footer></x-shell>')
            ->assertSee('Save it');
    }

    #[Test]
    public function every_bar_reaches_the_docs_and_the_marketplace(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->blade('<x-shell>Anything</x-shell>')
            ->assertSee('Docs')
            ->assertSee('Marketplace');

        $this->actingAs($user)
            ->blade('<x-account-layout>Anything</x-account-layout>')
            ->assertSee('Docs')
            ->assertSee('Marketplace');
    }

    #[Test]
    public function the_bar_of_a_screen_with_a_navigation_column_leaves_the_account_settings_to_it(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->blade('<x-shell>Anything</x-shell>')
            ->assertDontSee('Account settings');

        $this->actingAs($user)
            ->blade('<x-account-layout>Anything</x-account-layout>')
            ->assertSee('Account settings');
    }
}
