<?php

declare(strict_types=1);

namespace Tests\Feature\Views\Components;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TextareaTest extends TestCase
{
    #[Test]
    public function it_is_described_by_its_help_and_by_what_the_screen_puts_beside_it(): void
    {
        $this->blade('<x-textarea id="body" label="Your reply" help="Pam reads every message." described-by="body-markdown" />')
            ->assertSee('aria-describedby="body-help body-markdown"', escape: false);
    }

    #[Test]
    public function it_is_described_by_nothing_when_nothing_explains_it(): void
    {
        $this->blade('<x-textarea id="body" label="Your reply" />')
            ->assertDontSee('aria-describedby', escape: false);
    }
}
