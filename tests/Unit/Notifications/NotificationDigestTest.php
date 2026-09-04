<?php

declare(strict_types=1);

namespace Tests\Unit\Notifications;

use App\Enums\EmailType;
use App\Models\User;
use App\Notifications\NotificationDigest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NotificationDigestTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_takes_its_subject_from_the_heading(): void
    {
        $michael = User::factory()->create();

        $message = $this->notification()->toMail($michael);

        $this->assertEquals('Two reminders today', $message->subject);
    }

    #[Test]
    public function it_carries_every_line_it_was_handed(): void
    {
        $michael = User::factory()->create();

        $rendered = (string) $this->notification()->toMail($michael)->render();

        $this->assertStringContainsString('Birthday, Michael Scott', $rendered);
        $this->assertStringContainsString('It is in seven days.', $rendered);
        $this->assertStringContainsString('Anniversary, Jim Halpert', $rendered);
        $this->assertStringContainsString('It is today.', $rendered);
    }

    /**
     * The lines were worded when each deadline came due, in the language the
     * reader had then, so they reach the email exactly as they were written.
     */
    #[Test]
    public function it_sets_the_wording_of_each_reminder_apart_from_what_it_is_about(): void
    {
        $michael = User::factory()->create();

        $rendered = (string) $this->notification()->toMail($michael)->render();

        $this->assertStringContainsString('Birthday, Michael Scott</strong>', $rendered);
    }

    #[Test]
    public function it_words_nothing_again(): void
    {
        $michael = User::factory()->create();

        $message = $this->notification()->toMail($michael);

        $this->assertContains('**Birthday, Michael Scott**', $message->introLines);
        $this->assertContains('It is in seven days.', $message->introLines);
    }

    #[Test]
    public function it_says_the_summary_was_asked_for(): void
    {
        $michael = User::factory()->create();

        $message = $this->notification()->toMail($michael);

        $this->assertContains(
            'You asked for one message a day rather than one at a time. You can change that wherever your notifications are set up.',
            $message->introLines,
        );
    }

    #[Test]
    public function a_summary_with_one_thing_in_it_reads_as_that_one_thing(): void
    {
        $michael = User::factory()->create();

        $message = new NotificationDigest(
            heading: 'One reminder today',
            lines: [['title' => 'Birthday, Michael Scott', 'body' => 'It is today.']],
        )->toMail($michael);

        $this->assertContains('**Birthday, Michael Scott**', $message->introLines);
        $this->assertCount(3, $message->introLines);
    }

    #[Test]
    public function it_goes_out_as_an_email(): void
    {
        $michael = User::factory()->create();

        $this->assertEquals(['mail'], $this->notification()->via($michael));
    }

    #[Test]
    public function it_belongs_in_the_journal_as_a_summary(): void
    {
        $this->assertEquals(EmailType::NotificationDigest, $this->notification()->emailType());
    }

    private function notification(): NotificationDigest
    {
        return new NotificationDigest(
            heading: 'Two reminders today',
            lines: [
                ['title' => 'Birthday, Michael Scott', 'body' => 'It is in seven days.'],
                ['title' => 'Anniversary, Jim Halpert', 'body' => 'It is today.'],
            ],
        );
    }
}
