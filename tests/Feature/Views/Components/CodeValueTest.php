<?php

declare(strict_types=1);

namespace Tests\Feature\Views\Components;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CodeValueTest extends TestCase
{
    #[Test]
    public function it_sits_on_the_sunken_surface_unless_it_is_told_otherwise(): void
    {
        $this->blade('<x-code-value>mnc_live_7f3a</x-code-value>')
            ->assertSee('bg-sunken', escape: false)
            ->assertSee('font-mono text-sm break-all text-ink select-all', escape: false)
            ->assertSee('mnc_live_7f3a', escape: false);
    }

    #[Test]
    public function it_may_sit_on_the_canvas_instead(): void
    {
        $rendered = $this->blade('<x-code-value surface="canvas">https://monica.test/dav</x-code-value>');

        $rendered->assertSee('bg-canvas', escape: false);
        $rendered->assertDontSee('bg-sunken', escape: false);
    }

    #[Test]
    public function no_surface_carries_two_backgrounds(): void
    {
        foreach (['sunken', 'canvas'] as $surface) {
            $rendered = $this->blade('<x-code-value :surface="$surface">value</x-code-value>', ['surface' => $surface]);

            $this->assertSame(
                1,
                preg_match_all('/\bbg-(sunken|canvas)\b/', $rendered->__toString()),
                "The {$surface} surface names more than one background."
            );
        }
    }
}
