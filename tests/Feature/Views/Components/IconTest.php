<?php

declare(strict_types=1);

namespace Tests\Feature\Views\Components;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class IconTest extends TestCase
{
    #[Test]
    public function it_draws_the_default_glyph_when_the_name_is_not_one_it_knows(): void
    {
        $this->assertSame(
            (string) $this->blade('<x-icon name="profile" />'),
            (string) $this->blade('<x-icon name="spreadsheet" />'),
        );
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
        $this->assertNotSame(
            (string) $this->blade('<x-icon name="profile" />'),
            (string) $this->blade('<x-icon :name="$name" />', ['name' => $name]),
        );
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
    #[DataProvider('provideTheMarksTheTopBarAsksFor')]
    public function it_draws_a_glyph_of_its_own_for_every_place_the_top_bar_leads_to(string $name): void
    {
        $this->assertNotSame(
            (string) $this->blade('<x-icon name="profile" />'),
            (string) $this->blade('<x-icon :name="$name" />', ['name' => $name]),
        );
    }

    /** @return array<int, array<int, string>> */
    public static function provideTheMarksTheTopBarAsksFor(): array
    {
        return [
            ['shop'],
            ['shield'],
        ];
    }
}
