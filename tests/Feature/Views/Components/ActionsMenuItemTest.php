<?php

declare(strict_types=1);

namespace Tests\Feature\Views\Components;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ActionsMenuItemTest extends TestCase
{
    #[Test]
    public function it_announces_itself_as_a_line_of_the_menu(): void
    {
        $this->blade('<x-actions-menu.item href="/somewhere">Merge</x-actions-menu.item>')
            ->assertSee('role="menuitem"', escape: false)
            ->assertSee('href="/somewhere"', escape: false)
            ->assertSee('Merge');
    }

    #[Test]
    public function it_carries_a_glyph_only_where_one_was_given(): void
    {
        $withGlyph = $this->blade(
            '<x-actions-menu.item href="/somewhere">Merge<x-slot:icon><svg data-glyph></svg></x-slot:icon></x-actions-menu.item>',
        );

        $withGlyph->assertSee('data-glyph', escape: false);

        $this->blade('<x-actions-menu.item href="/somewhere">Merge</x-actions-menu.item>')
            ->assertDontSee('data-glyph', escape: false);
    }

    #[Test]
    public function it_takes_what_the_caller_hangs_on_it_without_losing_its_own_dressing(): void
    {
        $rendered = (string) $this->blade(
            '<x-actions-menu.item href="/somewhere" data-turbo="true" class="text-error">Delete</x-actions-menu.item>',
        );

        $this->assertStringContainsString('data-turbo="true"', $rendered);
        $this->assertStringContainsString('text-error', $rendered);
        $this->assertSame(1, mb_substr_count($rendered, 'class='));
    }
}
