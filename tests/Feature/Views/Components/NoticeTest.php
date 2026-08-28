<?php

declare(strict_types=1);

namespace Tests\Feature\Views\Components;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NoticeTest extends TestCase
{
    #[Test]
    public function it_is_quiet_unless_it_is_given_a_tone(): void
    {
        $this->blade('<x-notice>Nothing to point a key at yet.</x-notice>')
            ->assertSee('bg-sand border-hairline', escape: false)
            ->assertSee('Nothing to point a key at yet.', escape: false);
    }

    #[Test]
    public function each_tone_carries_a_glyph_as_well_as_a_tint(): void
    {
        $tones = [
            'info' => ['bg-brand/8', 'text-brand'],
            'success' => ['bg-success/8', 'text-success'],
            'warning' => ['bg-warning/8', 'text-warning'],
            'danger' => ['bg-error/8', 'text-error'],
        ];

        foreach ($tones as $tone => [$tint, $hue]) {
            $this->blade('<x-notice :tone="$tone">Something happened.</x-notice>', ['tone' => $tone])
                ->assertSee($tint, escape: false)
                ->assertSee($hue, escape: false)
                ->assertSee('<svg', escape: false);
        }
    }

    #[Test]
    public function it_may_be_given_a_title_above_its_sentence(): void
    {
        $rendered = $this->blade('<x-notice tone="warning" title="Every code is used">Ask for new ones.</x-notice>');

        $rendered->assertSee('<p class="text-base font-semibold text-ink">Every code is used</p>', escape: false);
        $rendered->assertSee('Ask for new ones.', escape: false);
    }

    #[Test]
    public function its_glyph_may_be_overridden_when_the_tone_is_not_what_it_is_about(): void
    {
        $this->blade('<x-notice icon="lock">You sign in through your identity provider.</x-notice>')
            ->assertSee('<rect x="3.4" y="7"', escape: false);
    }
}
