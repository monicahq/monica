<?php

declare(strict_types=1);

namespace Tests\Feature\Views\Components;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CaptionTest extends TestCase
{
    #[Test]
    public function it_is_soft_unless_it_is_given_a_tone(): void
    {
        $this->blade('<x-caption>Storage held</x-caption>')
            ->assertSee('text-[11px] font-medium tracking-caption uppercase text-muted-soft', escape: false)
            ->assertSee('Storage held', escape: false);
    }

    #[Test]
    public function the_muted_tone_draws_itself(): void
    {
        $this->blade('<x-caption tone="muted">Template</x-caption>')
            ->assertSee('text-muted', escape: false)
            ->assertDontSee('text-muted-soft', escape: false);
    }

    #[Test]
    public function no_tone_carries_two_text_colours(): void
    {
        foreach (['soft', 'muted'] as $tone) {
            $rendered = $this->blade('<x-caption :tone="$tone">Channels</x-caption>', ['tone' => $tone]);

            $this->assertSame(
                1,
                preg_match_all('/text-muted(-soft)?\b/', $rendered->__toString()),
                "The {$tone} tone names more than one text colour."
            );
        }
    }

    #[Test]
    public function it_carries_the_classes_it_is_given(): void
    {
        $this->blade('<x-caption class="mb-2 ml-2.5">Endpoints</x-caption>')
            ->assertSee('mb-2 ml-2.5', escape: false);
    }
}
