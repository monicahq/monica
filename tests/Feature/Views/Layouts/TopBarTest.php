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
    public function the_sidebar_shell_draws_its_bar_on_canvas(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->blade('<x-app-layout><x-slot:sidebar>Links</x-slot:sidebar>Anything</x-app-layout>')
            ->assertSee('border-b border-hairline-strong bg-canvas px-4 shadow-bar', escape: false);
    }

    #[Test]
    public function the_account_shell_draws_its_bar_on_canvas(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->blade('<x-account-layout>Anything</x-account-layout>')
            ->assertSee('border-b border-hairline-strong bg-canvas px-4 shadow-bar', escape: false);
    }
}
