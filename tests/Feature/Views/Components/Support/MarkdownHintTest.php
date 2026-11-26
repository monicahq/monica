<?php

declare(strict_types=1);

namespace Tests\Feature\Views\Components\Support;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MarkdownHintTest extends TestCase
{
    #[Test]
    public function it_says_markdown_is_accepted_and_points_at_where_the_marks_are_explained(): void
    {
        $this->blade('<x-support.markdown-hint id="body-markdown" />')
            ->assertSee('id="body-markdown"', escape: false)
            ->assertSee('You can format your message with Markdown.')
            ->assertSee('See how')
            ->assertSee('#format-your-message', escape: false);
    }
}
