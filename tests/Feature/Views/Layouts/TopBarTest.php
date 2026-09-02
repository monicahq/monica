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
    public function the_shell_draws_its_bar_as_a_solid_edge_over_the_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->blade('<x-shell>Anything</x-shell>')
            ->assertSee('border-b border-topbar-hairline bg-topbar', escape: false);
    }

    #[Test]
    public function the_account_shell_draws_the_same_bar(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->blade('<x-account-layout>Anything</x-account-layout>')
            ->assertSee('border-b border-topbar-hairline bg-topbar', escape: false);
    }

    #[Test]
    public function the_bar_of_a_screen_outside_a_vault_says_nothing_about_who_is_signed_in(): void
    {
        $user = User::factory()->create(['first_name' => 'Pam', 'last_name' => 'Beesly']);

        $rendered = $this->actingAs($user)->blade('<x-account-layout>Anything</x-account-layout>');

        $rendered->assertDontSee('PB');
        $rendered->assertDontSee($user->email);
        $rendered->assertSee('Switch between the light and dark theme');
    }

    /**
     * The bar says where you are, which is worth reading on arrival and not
     * worth 56px of every screen thereafter. It scrolls away with the screen, so
     * it is a solid strip rather than the page seen through a blur, and it covers
     * nothing a screen sticks to the top of its own scroll region.
     */
    #[Test]
    public function the_bar_scrolls_away_with_the_screen_rather_than_sticking_to_it(): void
    {
        $rendered = $this->actingAs(User::factory()->create())->blade('<x-shell>Anything</x-shell>');

        $rendered->assertSee('bg-topbar px-4', escape: false);
        $rendered->assertDontSee('backdrop-blur', escape: false);
        $rendered->assertDontSee('sticky top-0', escape: false);
        $rendered->assertDontSee('shadow-bar', escape: false);
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
            ->blade('<x-shell>Anything<x-slot:actions><span>Add someone</span></x-slot:actions></x-shell>');

        $rendered->assertSee('Add someone');
        $rendered->assertDontSee($user->email);
    }

    #[Test]
    public function it_opens_the_drawer_from_the_bar_below_the_wide_breakpoint(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->blade('<x-shell>Anything</x-shell>')
            ->assertSee('aria-controls="main-nav"', escape: false)
            ->assertSee('aria-label="Open the menu"', escape: false)
            ->assertSee('lg:hidden', escape: false);
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
    public function a_screen_with_a_footer_gets_a_bar_at_the_foot_of_the_scroll_region(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->blade('<x-shell>Anything<x-slot:footer><span>Save it</span></x-slot:footer></x-shell>')
            ->assertSee('Save it')
            ->assertSee('sticky bottom-0', escape: false);
    }

    #[Test]
    public function a_wide_screen_gives_up_the_reading_column(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->blade('<x-shell wide>Anything</x-shell>')
            ->assertDontSee('max-w-240', escape: false);
    }

    #[Test]
    public function a_wide_screen_keeps_the_gutter_every_other_screen_has(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->blade('<x-shell wide>Anything</x-shell>')
            ->assertSee('px-4 pt-6 sm:px-6 sm:pt-7', escape: false)
            ->assertSee('pb-6 sm:pb-7', escape: false);

        $this->actingAs($user)
            ->blade('<x-shell>Anything</x-shell>')
            ->assertSee('px-4 pt-6 sm:px-6 sm:pt-7', escape: false);
    }

    /**
     * The window is exactly as tall as the viewport and never scrolls. The
     * screen on the right is the one thing that does, which is what keeps the
     * navigation column and its foot where they are however much or little a
     * screen holds, and whatever a screen opens or closes inside itself.
     *
     * It is one layout and not two: a wide screen scrolls in the same place as
     * every other one, and used to arrange its own.
     */
    #[Test]
    public function the_screen_is_the_one_thing_that_scrolls(): void
    {
        $user = User::factory()->create();

        foreach (['<x-shell>Anything</x-shell>', '<x-shell wide>Anything</x-shell>'] as $shell) {
            $rendered = $this->actingAs($user)->blade($shell);

            $rendered->assertSee('grid h-dvh grid-cols-1 overflow-hidden', escape: false);
            $rendered->assertSee('relative min-w-0 overflow-y-auto', escape: false);
        }
    }

    /**
     * The `relative` on the scroll region is what stops the window scrolling,
     * and it is easy to lose because nothing on the screen shows it doing its
     * work. An `sr-only` element is absolutely positioned, so without it the
     * element is laid out against the document instead of against the region,
     * the document grows taller than the window, and the window scrolling lifts
     * the whole window-height layout with the navigation column inside it.
     */
    #[Test]
    public function the_scroll_region_is_positioned_so_nothing_inside_it_can_grow_the_document(): void
    {
        $rendered = $this->actingAs(User::factory()->create())->blade('<x-shell>Anything</x-shell>');

        $rendered->assertSee('class="relative min-w-0 overflow-y-auto"', escape: false);
    }

    /**
     * The scrim over the drawer catches a click and not a wheel or a swipe, so
     * the screen behind it is what has to stop scrolling. It is said on the
     * screen rather than on the body, which is not what scrolls.
     */
    #[Test]
    public function the_open_drawer_stops_the_screen_behind_it_scrolling(): void
    {
        $rendered = $this->actingAs(User::factory()->create())->blade('<x-shell>Anything</x-shell>');

        $rendered->assertSee("navOpen && 'max-lg:overflow-hidden'", escape: false);
        $rendered->assertDontSee('document.body.classList', escape: false);
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
