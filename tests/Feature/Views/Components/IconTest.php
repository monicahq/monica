<?php

declare(strict_types=1);

namespace Tests\Feature\Views\Components;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class IconTest extends TestCase
{
    #[Test]
    public function it_draws_the_glyph_it_is_asked_for(): void
    {
        $rendered = $this->blade('<x-icon name="journal" />');

        $rendered->assertSee('viewBox="0 0 16 16"', escape: false);
        $rendered->assertSee('M8 4.6C6.8 3.6 5.2 3.2 3 3.2', escape: false);
    }

    #[Test]
    public function it_draws_the_default_glyph_when_the_name_is_not_one_it_knows(): void
    {
        $rendered = $this->blade('<x-icon name="spreadsheet" />');

        $rendered->assertSee('<circle cx="8" cy="6" r="2.6">', escape: false);
    }

    #[Test]
    public function it_hides_itself_from_screen_readers_unless_it_is_given_a_name(): void
    {
        $this->blade('<x-icon name="mail" />')
            ->assertSee('aria-hidden="true"', escape: false)
            ->assertDontSee('role="img"', escape: false);

        $this->blade('<x-icon name="mail" label="Email" />')
            ->assertSee('role="img"', escape: false)
            ->assertSee('<title>Email</title>', escape: false)
            ->assertDontSee('aria-hidden', escape: false);
    }

    #[Test]
    #[DataProvider('provideTheMarksTheFieldTypeCatalogueAsksFor')]
    public function it_draws_a_glyph_of_its_own_for_every_mark_the_catalogue_asks_for(string $name): void
    {
        $this->blade('<x-icon name="'.$name.'" />')
            ->assertSee('viewBox="0 0 16 16"', escape: false)
            ->assertDontSee('<circle cx="8" cy="6" r="2.6">', escape: false);
    }

    /** @return array<int, array<int, string>> */
    public static function provideTheMarksTheFieldTypeCatalogueAsksFor(): array
    {
        return [
            ['text'],
            ['text-lines'],
            ['hash'],
            ['radio'],
            ['tag'],
            ['calendar-repeat'],
            ['clock'],
            ['layers'],
            ['coin'],
            ['divider'],
            ['heading'],
            ['swatch'],
        ];
    }

    #[Test]
    public function it_is_drawn_at_the_size_and_weight_it_is_asked_for(): void
    {
        $rendered = $this->blade('<x-icon name="heart" :size="23" :stroke-width="1.6" />');

        $rendered->assertSee('width="23"', escape: false);
        $rendered->assertSee('height="23"', escape: false);
        $rendered->assertSee('stroke-width="1.6"', escape: false);
    }
}
