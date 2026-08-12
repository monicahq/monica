<?php

declare(strict_types=1);

namespace Tests\Feature\Views\Components;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BarLinksTest extends TestCase
{
    #[Test]
    public function it_offers_the_docs_and_the_marketplace(): void
    {
        $this->blade('<x-bar-links />')
            ->assertSee('Docs')
            ->assertSee('Marketplace');
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
}
