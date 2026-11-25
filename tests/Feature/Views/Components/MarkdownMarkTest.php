<?php

declare(strict_types=1);

namespace Tests\Feature\Views\Components;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MarkdownMarkTest extends TestCase
{
    #[Test]
    public function it_hides_itself_from_screen_readers_unless_it_is_given_a_name(): void
    {
        $this->blade('<x-markdown-mark />')
            ->assertSee('aria-hidden="true"', escape: false)
            ->assertDontSee('role="img"', escape: false);

        $this->blade('<x-markdown-mark label="Markdown" />')
            ->assertSee('role="img"', escape: false)
            ->assertSee('aria-label="Markdown"', escape: false)
            ->assertSee('<title>Markdown</title>', escape: false)
            ->assertDontSee('aria-hidden', escape: false);
    }
}
