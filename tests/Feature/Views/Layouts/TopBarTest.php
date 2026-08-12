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
    public function the_shell_draws_its_bar_on_canvas(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->blade('<x-shell>Anything</x-shell>')
            ->assertSee('border-b border-hairline-strong bg-canvas px-4 shadow-bar', escape: false);
    }

    #[Test]
    public function what_sits_in_the_bar_is_not_the_colour_of_the_bar(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->blade('<x-shell><x-slot:bar><x-button.bar href="/vaults">Your vaults</x-button.bar></x-slot:bar>Anything</x-shell>')
            ->assertSee('bg-sand text-ink shadow-raised', escape: false);
    }

    #[Test]
    public function the_account_shell_draws_its_bar_on_canvas(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->blade('<x-account-layout>Anything</x-account-layout>')
            ->assertSee('border-b border-hairline-strong bg-canvas px-4 shadow-bar', escape: false);
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
}
