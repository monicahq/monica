<?php

declare(strict_types=1);

namespace Tests\Feature\Views\Layouts;

use App\Models\Administrator;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdminLayoutTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_says_which_of_the_two_products_you_are_looking_at(): void
    {
        $this->actingAs($this->administrator())
            ->blade('<x-admin-layout>Anything</x-admin-layout>')
            ->assertSee('Admin space')
            ->assertSee('You are looking at the instance, not at your own account.');
    }

    #[Test]
    public function it_does_not_draw_its_bar_the_colour_the_ordinary_bars_are(): void
    {
        $this->actingAs($this->administrator())
            ->blade('<x-admin-layout>Anything</x-admin-layout>')
            ->assertSee('border-b border-hairline-strong bg-accent-tint shadow-bar', escape: false)
            ->assertDontSee('bg-canvas px-4 shadow-bar', escape: false);
    }

    #[Test]
    public function it_offers_no_way_into_a_vault(): void
    {
        $this->actingAs($this->administrator())
            ->blade('<x-admin-layout>Anything</x-admin-layout>')
            ->assertDontSee('/vaults', escape: false);
    }

    #[Test]
    public function it_offers_a_way_back_to_the_ordinary_application(): void
    {
        $this->actingAs($this->administrator())
            ->blade('<x-admin-layout>Anything</x-admin-layout>')
            ->assertSee('Leave the admin space');
    }

    #[Test]
    public function it_carries_no_dock_since_the_dock_points_into_a_vault(): void
    {
        $this->actingAs($this->administrator())
            ->blade('<x-admin-layout>Anything</x-admin-layout>')
            ->assertDontSee('x-dock', escape: false)
            ->assertDontSee('aria-label="Vaults"', escape: false);
    }

    private function administrator(): User
    {
        $michael = User::factory()->create(['first_name' => 'Michael', 'last_name' => 'Scott']);
        Administrator::factory()->create(['user_id' => $michael->id]);

        return $michael;
    }
}
