<?php

declare(strict_types=1);

namespace Tests\Feature\Views\Components;

use Illuminate\Testing\TestView;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TooltipTest extends TestCase
{
    #[Test]
    public function the_bubble_waits_for_the_pointer(): void
    {
        $this->render()
            ->assertSee('@mouseenter="shown = true"', escape: false)
            ->assertSee('@mouseleave="shown = false"', escape: false)
            ->assertSee('x-show="shown"', escape: false)
            ->assertSee('x-cloak', escape: false);
    }

    #[Test]
    public function the_sentence_is_the_name_of_the_whole_mark_and_is_never_said_twice(): void
    {
        $this->render()
            ->assertSee('role="img"', escape: false)
            ->assertSee('aria-hidden="true"', escape: false);
    }

    #[Test]
    public function one_reached_by_keyboard_is_a_button_that_its_sentence_describes(): void
    {
        $rendered = $this->blade('<x-tooltip text="Every year on 3 February" :focusable="true">In 3 days</x-tooltip>');

        $rendered->assertSee('<button', escape: false)
            ->assertSee('type="button"', escape: false)
            ->assertDontSee('role="img"', escape: false)
            ->assertDontSee('aria-label', escape: false);

        $this->assertSame(1, preg_match('/aria-describedby="(tooltip-\w+)"/', (string) $rendered, $described));
        $rendered->assertSee("id=\"{$described[1]}\" role=\"tooltip\"", escape: false);
    }

    #[Test]
    public function one_that_only_answers_the_pointer_holds_no_button(): void
    {
        $this->render()
            ->assertDontSee('<button', escape: false)
            ->assertDontSee('role="tooltip"', escape: false);
    }

    private function render(): TestView
    {
        return $this->blade('<x-tooltip text="Every year on 3 February"><x-icon name="alarm" :size="15" /></x-tooltip>');
    }
}
