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
    public function it_offers_the_docs_the_marketplace_and_the_account_settings(): void
    {
        $this->blade('<x-bar-links />')
            ->assertSee('Docs')
            ->assertSee('Marketplace')
            ->assertSee('Account settings');
    }

    #[Test]
    public function it_leaves_the_account_settings_to_the_navigation_column_that_carries_them(): void
    {
        $rendered = $this->blade('<x-bar-links :settings="false" />');

        $rendered->assertDontSee('Account settings');
        $rendered->assertDontSee('href="'.route('settings.index').'"', escape: false);
        $rendered->assertSee('Marketplace');
        $rendered->assertSee('Docs');
    }

    #[Test]
    public function it_leads_to_the_catalogue_the_settings_and_nowhere_yet_for_the_docs(): void
    {
        $this->blade('<x-bar-links />')
            ->assertSee('href="'.route('marketplace.index').'"', escape: false)
            ->assertSee('href="'.route('settings.index').'"', escape: false)
            ->assertSee('href="#"', escape: false);
    }

    #[Test]
    public function every_link_says_its_name_to_a_screen_reader(): void
    {
        $this->blade('<x-bar-links />')
            ->assertSee('aria-label="Docs"', escape: false)
            ->assertSee('aria-label="Marketplace"', escape: false)
            ->assertSee('aria-label="Account settings"', escape: false);
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
