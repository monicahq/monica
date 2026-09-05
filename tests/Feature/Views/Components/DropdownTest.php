<?php

declare(strict_types=1);

namespace Tests\Feature\Views\Components;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DropdownTest extends TestCase
{
    /** @return array<int, array{value: string, label: string, hint: string, selected: bool}> */
    private function formats(): array
    {
        return [
            ['value' => '24', 'label' => '24-hour', 'hint' => '14:00', 'selected' => true],
            ['value' => '12', 'label' => '12-hour', 'hint' => '2:00 PM', 'selected' => false],
        ];
    }

    #[Test]
    public function it_shows_what_is_chosen_on_the_button_that_opens_it(): void
    {
        $rendered = $this->blade(
            '<x-dropdown name="time_format" label="24-hour" :options="$options" title="Time format" />',
            ['options' => $this->formats()],
        );

        $rendered->assertSee('bg-canvas text-ink hover:bg-hover', escape: false);
        $rendered->assertSee('aria-haspopup="menu"', escape: false);
        $rendered->assertSee('24-hour', escape: false);
        $rendered->assertSee('<p class="px-2.5 pt-2 pb-1.5 uppercase text-xs tracking-caption text-muted">Time format</p>', escape: false);
    }

    #[Test]
    public function every_choice_is_a_submit_button_so_it_works_without_javascript(): void
    {
        $rendered = $this->blade(
            '<x-dropdown name="time_format" label="24-hour" :options="$options" />',
            ['options' => $this->formats()],
        );

        $rendered->assertSee('type="submit"', escape: false);
        $rendered->assertSee('name="time_format"', escape: false);
        $rendered->assertSee('value="24"', escape: false);
        $rendered->assertSee('value="12"', escape: false);
    }

    #[Test]
    public function what_is_already_chosen_is_marked_by_more_than_a_tick(): void
    {
        $rendered = $this->blade(
            '<x-dropdown name="time_format" label="24-hour" :options="$options" />',
            ['options' => $this->formats()],
        );

        $rendered->assertSee('aria-checked="true"', escape: false);
        $this->assertSame(1, substr_count($rendered->__toString(), 'aria-checked="true"'));
    }

    #[Test]
    public function it_is_pinned_to_its_button_so_a_panel_cannot_clip_it(): void
    {
        $rendered = $this->blade(
            '<x-dropdown name="time_format" label="24-hour" :options="$options" />',
            ['options' => $this->formats()],
        );

        $rendered->assertSee('x-ref="trigger"', escape: false);
        $rendered->assertSee('x-anchor.bottom-end.offset.6="$refs.trigger"', escape: false);
    }

    #[Test]
    public function it_writes_machine_values_in_mono_when_it_is_asked_to(): void
    {
        $this->blade('<x-dropdown name="t" label="24-hour" :options="$options" monospaced-hints />', ['options' => $this->formats()])
            ->assertSee('ml-auto text-xs text-muted font-mono', escape: false);

        $this->blade('<x-dropdown name="t" label="English" :options="$options" />', ['options' => $this->formats()])
            ->assertDontSee('font-mono', escape: false);
    }
}
