<?php

declare(strict_types=1);

namespace Tests\Feature\Views\Components;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ButtonTest extends TestCase
{
    #[Test]
    public function each_variant_draws_itself(): void
    {
        $this->blade('<x-button>Save</x-button>')->assertSee('bg-action-primary', escape: false);
        $this->blade('<x-button.secondary>Cancel</x-button.secondary>')->assertSee('bg-canvas', escape: false);
        $this->blade('<x-button.bar>Your vaults</x-button.bar>')->assertSee('border-hairline-strong bg-canvas text-ink', escape: false);
        $this->blade('<x-button.ghost>Remove</x-button.ghost>')->assertSee('hover:bg-hover hover:text-ink', escape: false);
        $this->blade('<x-button.danger>Delete it for good</x-button.danger>')->assertSee('bg-error', escape: false);
    }

    #[Test]
    public function the_bar_variant_is_drawn_the_same_way_as_the_secondary_one(): void
    {
        $bar = (string) $this->blade('<x-button.bar>Your vaults</x-button.bar>');
        $secondary = (string) $this->blade('<x-button.secondary>Your vaults</x-button.secondary>');

        $this->assertSame($secondary, $bar);
    }

    #[Test]
    public function the_secondary_button_owns_its_edge_rather_than_borrowing_the_panel_hairline(): void
    {
        $this->blade('<x-button.secondary>Cancel</x-button.secondary>')
            ->assertSee('border-hairline-strong', escape: false)
            ->assertSee('shadow-control', escape: false)
            ->assertSee('hover:bg-hover hover:border-muted-soft', escape: false)
            ->assertSee('focus-visible:border-focus', escape: false);
    }

    #[Test]
    public function no_variant_carries_two_border_colours(): void
    {
        $variants = [
            '<x-button>Save</x-button>',
            '<x-button.secondary>Cancel</x-button.secondary>',
            '<x-button.bar>Your vaults</x-button.bar>',
            '<x-button.ghost>Remove</x-button.ghost>',
            '<x-button.danger>Delete it for good</x-button.danger>',
        ];

        foreach ($variants as $variant) {
            $classes = (string) $this->blade($variant);

            $this->assertSame(
                1,
                preg_match_all('/ border-(?:transparent|hairline-strong)[ "]/', $classes),
                "{$variant} names its border colour more than once, and the one the browser draws is whichever Tailwind emitted last.",
            );
        }
    }

    #[Test]
    public function the_secondary_button_drops_its_shadow_when_it_is_disabled(): void
    {
        $this->blade('<x-button.secondary disabled>Cancel</x-button.secondary>')
            ->assertSee('disabled:shadow-none', escape: false);
    }

    #[Test]
    public function the_filled_buttons_take_neither_the_edge_nor_the_shadow(): void
    {
        $this->blade('<x-button>Save</x-button>')
            ->assertDontSee('shadow-control', escape: false)
            ->assertDontSee('border-hairline-strong', escape: false);

        $this->blade('<x-button.danger>Delete it for good</x-button.danger>')
            ->assertDontSee('shadow-control', escape: false);

        $this->blade('<x-button.ghost>Remove</x-button.ghost>')
            ->assertDontSee('shadow-control', escape: false);
    }

    #[Test]
    public function it_is_the_default_height_unless_it_is_asked_to_be_small(): void
    {
        $this->blade('<x-button>Save</x-button>')->assertSee('h-9 px-3.75', escape: false);
        $this->blade('<x-button size="small">Save</x-button>')->assertSee('h-8 px-3', escape: false);
    }

    #[Test]
    public function it_becomes_an_anchor_when_it_is_given_somewhere_to_go(): void
    {
        $this->blade('<x-button href="/vaults">Your vaults</x-button>')
            ->assertSee('<a href="/vaults"', escape: false)
            ->assertDontSee('<button', escape: false);

        $this->blade('<x-button>Save</x-button>')
            ->assertSee('<button type="submit"', escape: false);
    }

    #[Test]
    public function a_class_written_on_it_still_wins(): void
    {
        $this->blade('<x-button class="ml-auto">Save</x-button>')
            ->assertSee('h-9 px-3.75 text-base ml-auto', escape: false);
    }
}
