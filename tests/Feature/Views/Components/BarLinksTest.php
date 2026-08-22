<?php

declare(strict_types=1);

namespace Tests\Feature\Views\Components;

use App\Models\Administrator;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BarLinksTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_offers_the_docs_and_the_marketplace(): void
    {
        $this->blade('<x-bar-links />')
            ->assertSee('Docs')
            ->assertSee('Marketplace');
    }

    #[Test]
    public function it_leads_to_the_catalogue_and_nowhere_yet_for_the_docs(): void
    {
        $this->blade('<x-bar-links />')
            ->assertSee('href="'.route('marketplace.index').'"', escape: false)
            ->assertSee('href="#"', escape: false);
    }

    #[Test]
    public function it_is_navigation_rather_than_an_action(): void
    {
        $this->blade('<x-bar-links />')
            ->assertSee('text-sm text-body', escape: false)
            ->assertSee('hover:bg-sand hover:text-ink', escape: false)
            ->assertSee('focus-visible:outline-focus-ring', escape: false);
    }

    #[Test]
    public function it_keeps_the_glyphs_and_drops_the_labels_on_a_narrow_window(): void
    {
        $this->blade('<x-bar-links />')
            ->assertSee('hidden min-[900px]:inline', escape: false)
            ->assertSee('aria-label="Docs"', escape: false)
            ->assertSee('aria-label="Marketplace"', escape: false);
    }

    #[Test]
    public function it_is_parted_from_the_theme_switch_and_the_avatar_by_a_hairline(): void
    {
        $this->blade('<x-bar-links />')
            ->assertSee('h-5.5 w-px bg-hairline-strong', escape: false);
    }

    #[Test]
    public function it_says_nothing_about_the_admin_space_to_a_regular_user(): void
    {
        $jim = User::factory()->create();

        $this->actingAs($jim)
            ->blade('<x-bar-links />')
            ->assertDontSee('/admin', escape: false)
            ->assertDontSee('Admin space');
    }

    #[Test]
    public function it_says_nothing_about_the_admin_space_to_somebody_signed_out(): void
    {
        $this->blade('<x-bar-links />')
            ->assertDontSee('/admin', escape: false)
            ->assertDontSee('Admin space');
    }

    #[Test]
    public function it_offers_the_admin_space_to_whoever_runs_the_instance(): void
    {
        $michael = User::factory()->create();
        Administrator::factory()->create(['user_id' => $michael->id]);

        $this->actingAs($michael)
            ->blade('<x-bar-links />')
            ->assertSee(route('admin.overview.index'), escape: false)
            ->assertSee('Admin space');
    }

    #[Test]
    public function it_takes_the_admin_space_back_off_an_administrator_who_cannot_sign_in(): void
    {
        $michael = User::factory()->create(['is_active' => false]);
        Administrator::factory()->create(['user_id' => $michael->id]);

        $this->actingAs($michael)
            ->blade('<x-bar-links />')
            ->assertDontSee('/admin', escape: false);
    }
}
