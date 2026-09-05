<?php

declare(strict_types=1);

namespace Tests\Feature\Views\Components;

use App\Enums\VaultColourEnum;
use App\Models\Section;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SectionMarkTest extends TestCase
{
    #[Test]
    public function it_draws_the_glyph_it_is_given(): void
    {
        $this->blade('<x-section-mark icon="health" />')
            ->assertSee('M6.5 2.6h3v3.9h3.9v3h-3.9v3.9h-3V9.5H2.6v-3h3.9z', escape: false);
    }

    #[Test]
    public function it_draws_the_default_glyph_when_it_is_given_none(): void
    {
        $this->blade('<x-section-mark />')
            ->assertSee('M4 2.4h5.2L12.4 5.6V13.6H4z', escape: false);
    }

    #[Test]
    public function it_draws_the_default_glyph_for_a_name_a_section_may_not_use(): void
    {
        $this->blade('<x-section-mark icon="chevron-down" />')
            ->assertSee('M4 2.4h5.2L12.4 5.6V13.6H4z', escape: false);

        $this->blade('<x-section-mark icon="not-a-glyph" />')
            ->assertSee('M4 2.4h5.2L12.4 5.6V13.6H4z', escape: false);
    }

    #[Test]
    public function it_tints_itself_from_the_colour_it_is_given(): void
    {
        $this->blade('<x-section-mark icon="health" colour="#4f8a49" />')
            ->assertSee(VaultColourEnum::Green->tint(), escape: false);
    }

    #[Test]
    public function it_falls_back_to_neutral_for_a_colour_the_set_does_not_offer(): void
    {
        $this->blade('<x-section-mark icon="health" />')
            ->assertSee(VaultColourEnum::neutralTint(), escape: false);

        $this->blade('<x-section-mark icon="health" colour="#ff0000" />')
            ->assertSee(VaultColourEnum::neutralTint(), escape: false);
    }

    #[Test]
    public function it_is_hidden_from_a_screen_reader_because_the_heading_beside_it_already_names_the_section(): void
    {
        $this->blade('<x-section-mark icon="health" colour="#4f8a49" />')
            ->assertSee('aria-hidden="true"', escape: false);
    }

    #[Test]
    public function it_draws_every_glyph_a_section_may_be_marked_with(): void
    {
        foreach (Section::ICONS as $icon) {
            $rendered = $this->blade('<x-section-mark :icon="$icon" />', ['icon' => $icon])->__toString();

            $this->assertStringContainsString('<svg', $rendered, "{$icon} drew nothing.");
        }
    }
}
