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
            ->assertSee('erica-badge erica-badge--neutral', escape: false)
            ->assertSee('Draft', escape: false);
    }

    #[Test]
    public function each_tone_draws_itself(): void
    {
        foreach (['success', 'warning', 'danger', 'info', 'accent', 'outline'] as $tone) {
            $this->blade('<x-badge :tone="$tone">State</x-badge>', ['tone' => $tone])
                ->assertSee('erica-badge--'.$tone, escape: false);
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
