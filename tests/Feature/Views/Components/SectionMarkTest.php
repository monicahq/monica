<?php

declare(strict_types=1);

namespace Tests\Feature\Views\Components;

use App\Models\Section;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SectionMarkTest extends TestCase
{
    #[Test]
    public function it_falls_back_to_the_default_glyph_for_a_name_a_section_may_not_use(): void
    {
        $default = (string) $this->blade('<x-section-mark :icon="$icon" />', ['icon' => Section::DEFAULT_ICON]);

        $this->assertSame($default, (string) $this->blade('<x-section-mark />'));
        $this->assertSame($default, (string) $this->blade('<x-section-mark icon="chevron-down" />'));
        $this->assertSame($default, (string) $this->blade('<x-section-mark icon="not-a-glyph" />'));
    }

    #[Test]
    public function it_is_hidden_from_a_screen_reader_because_the_heading_beside_it_already_names_the_section(): void
    {
        $this->blade('<x-section-mark icon="health" colour="#4f8a49" />')
            ->assertSee('aria-hidden="true"', escape: false);
    }
}
