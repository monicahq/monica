<?php

declare(strict_types=1);

namespace Tests\Feature\Views\Components;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BadgeTest extends TestCase
{
    #[Test]
    public function it_is_neutral_unless_it_is_given_a_tone(): void
    {
        $this->blade('<x-badge>Draft</x-badge>')
            ->assertSee('bg-sand text-ink-soft', escape: false)
            ->assertSee('Draft', escape: false);
    }

    #[Test]
    public function each_tone_draws_itself(): void
    {
        $tints = [
            'success' => 'bg-success/10 text-success',
            'warning' => 'bg-warning/10 text-warning',
            'danger' => 'bg-error/10 text-error',
            'info' => 'bg-brand/10 text-brand',
            'accent' => 'bg-accent-tint text-brand',
        ];

        foreach ($tints as $tone => $tint) {
            $this->blade('<x-badge :tone="$tone">State</x-badge>', ['tone' => $tone])
                ->assertSee($tint, escape: false);
        }
    }

    #[Test]
    public function it_always_carries_the_word_that_the_colour_only_repeats(): void
    {
        $rendered = $this->blade('<x-badge tone="danger">Bounced</x-badge>');

        $rendered->assertSee('Bounced', escape: false);
        $rendered->assertDontSee('sr-only', escape: false);
    }

    #[Test]
    public function it_may_carry_a_glyph_beside_the_word(): void
    {
        $this->blade('<x-badge tone="success" icon="check">Delivered</x-badge>')
            ->assertSee('<svg', escape: false)
            ->assertSee('Delivered', escape: false);
    }
}
