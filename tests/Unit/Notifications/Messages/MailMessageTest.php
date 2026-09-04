<?php

declare(strict_types=1);

namespace Tests\Unit\Notifications\Messages;

use App\Notifications\Messages\MailMessage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MailMessageTest extends TestCase
{
    #[Test]
    public function it_boxes_the_panel(): void
    {
        $rendered = (string) new MailMessage()
            ->line('Somebody tried to sign in.')
            ->panel('Change your password now.')
            ->render();

        $this->assertStringContainsString('class="panel"', $rendered);
        $this->assertStringContainsString('Change your password now.', $rendered);
    }

    #[Test]
    public function it_boxes_nothing_when_there_is_no_panel(): void
    {
        $rendered = (string) new MailMessage()
            ->line('Somebody tried to sign in.')
            ->render();

        $this->assertStringNotContainsString('class="panel"', $rendered);
    }

    #[Test]
    public function it_reads_the_panel_after_the_button(): void
    {
        $rendered = (string) new MailMessage()
            ->action('Sign in to Monica', 'https://monica.test/magic-link/abc')
            ->panel('Ignore this email if you did not ask for it.')
            ->render();

        $this->assertLessThan(
            mb_strpos($rendered, 'Ignore this email if you did not ask for it.'),
            mb_strpos($rendered, 'Sign in to Monica'),
        );
    }

    #[Test]
    public function it_reads_the_panel_before_an_outro_line(): void
    {
        $rendered = (string) new MailMessage()
            ->line('Version 3 of Dundie Awards has not been accepted.')
            ->panel('The description does not say what it is for.')
            ->outroLine('You can submit a new version whenever you are ready.')
            ->render();

        $this->assertLessThan(
            mb_strpos($rendered, 'You can submit a new version whenever you are ready.'),
            mb_strpos($rendered, 'The description does not say what it is for.'),
        );
    }

    #[Test]
    public function it_reads_an_outro_line_even_though_there_is_no_button(): void
    {
        $message = new MailMessage()
            ->line('Version 3 of Dundie Awards has not been accepted.')
            ->outroLine('You can submit a new version whenever you are ready.');

        $this->assertEquals(['Version 3 of Dundie Awards has not been accepted.'], $message->introLines);
        $this->assertEquals(['You can submit a new version whenever you are ready.'], $message->outroLines);
    }
}
