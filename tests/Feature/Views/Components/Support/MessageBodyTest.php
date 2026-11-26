<?php

declare(strict_types=1);

namespace Tests\Feature\Views\Components\Support;

use App\Helpers\SupportMarkdown;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MessageBodyTest extends TestCase
{
    #[Test]
    public function it_draws_the_message_through_the_support_renderer(): void
    {
        $markdown = "# Steps\n\n1. Open **the vault**\n2. Ask [Pam](https://example.com/pam)";

        $this->blade('<x-support.message-body :markdown="$markdown" />', ['markdown' => $markdown])
            ->assertSee(SupportMarkdown::toHtml($markdown), escape: false)
            ->assertDontSee('**the vault**', escape: false);
    }
}
