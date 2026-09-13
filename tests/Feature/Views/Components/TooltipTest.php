<?php

declare(strict_types=1);

namespace Tests\Feature\Views\Components;

use Illuminate\Testing\TestView;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TooltipTest extends TestCase
{
    #[Test]
    public function it_says_the_sentence_the_mark_stands_for(): void
    {
        $this->render()
            ->assertSee('Every year on 3 February', escape: false);
    }

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
            ->assertSee('aria-label="Every year on 3 February"', escape: false)
            ->assertSee('aria-hidden="true"', escape: false);
    }

    private function render(): TestView
    {
        return $this->blade('<x-tooltip text="Every year on 3 February"><x-icon name="alarm" :size="15" /></x-tooltip>');
    }
}
