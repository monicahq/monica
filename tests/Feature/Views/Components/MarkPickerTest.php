<?php

declare(strict_types=1);

namespace Tests\Feature\Views\Components;

use Illuminate\Testing\TestView;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MarkPickerTest extends TestCase
{
    #[Test]
    public function the_combined_picker_names_its_popover_and_gives_focus_back_on_escape(): void
    {
        $rendered = $this->picker('combined');

        $rendered->assertSee('aria-haspopup="dialog"', escape: false);
        $rendered->assertSee('aria-controls="dwight-mark-picker"', escape: false);
        $rendered->assertSee('id="dwight-mark-picker"', escape: false);
        $rendered->assertSee('role="dialog"', escape: false);
        $rendered->assertSee('aria-label="Mark"', escape: false);
        $rendered->assertSee('@keydown.escape.window="if (open) { open = false; $refs.trigger.focus() }"', escape: false);
    }

    #[Test]
    public function each_split_picker_gives_focus_back_to_its_own_trigger_on_escape(): void
    {
        $rendered = $this->picker('split');

        $rendered->assertSee('aria-controls="dwight-icon-picker"', escape: false);
        $rendered->assertSee('aria-controls="dwight-colour-picker"', escape: false);
        $rendered->assertSee('@keydown.escape.window="if (openIcon) { openIcon = false; $refs.iconTrigger.focus() }"', escape: false);
        $rendered->assertSee('@keydown.escape.window="if (openColour) { openColour = false; $refs.colourTrigger.focus() }"', escape: false);
    }

    private function picker(string $layout): TestView
    {
        return $this->blade(
            '<x-mark-picker id="dwight" :glyphs="$glyphs" :colours="$colours" :layout="$layout" />',
            [
                'layout' => $layout,
                'glyphs' => ['heart', 'star'],
                'colours' => [
                    ['value' => null, 'label' => 'No colour', 'tint' => '', 'swatch' => null],
                    ['value' => 'beet', 'label' => 'Beet', 'tint' => '', 'swatch' => '#8a2be2'],
                ],
            ],
        );
    }
}
