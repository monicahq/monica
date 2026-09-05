<?php

declare(strict_types=1);

namespace Tests\Unit\Notifications;

use App\Enums\EmailType;
use App\Models\User;
use App\Notifications\ReminderDue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ReminderDueTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_takes_its_subject_from_what_the_reminder_says(): void
    {
        $michael = User::factory()->create();

        $message = $this->notification()->toMail($michael);

        $this->assertEquals('Birthday, Michael Scott', $message->subject);
    }

    #[Test]
    public function it_names_the_record_the_date_and_how_far_off_it_is(): void
    {
        $michael = User::factory()->create();

        $rendered = (string) $this->notification()->toMail($michael)->render();

        $this->assertStringContainsString('Birthday, Michael Scott', $rendered);
        $this->assertStringContainsString('15 March 2027', $rendered);
        $this->assertStringContainsString('It is in seven days.', $rendered);
    }

    #[Test]
    public function it_says_which_date_it_is_about_rather_than_todays(): void
    {
        $michael = User::factory()->create();

        $message = $this->notification()->toMail($michael);

        $this->assertContains(
            'It is about Michael Scott, and the date it reads is 15 March 2027.',
            $message->introLines,
        );
    }

    #[Test]
    public function it_says_where_the_reminder_can_be_changed(): void
    {
        $michael = User::factory()->create();

        $message = $this->notification()->toMail($michael);

        $this->assertContains(
            'You asked to be told this when you added the reminder. You can change it, or stop it, wherever that date is written down.',
            $message->introLines,
        );
    }

    #[Test]
    public function it_goes_out_as_an_email(): void
    {
        $michael = User::factory()->create();

        $this->assertEquals(['mail'], $this->notification()->via($michael));
    }

    #[Test]
    public function it_goes_on_the_queue_a_deadline_cannot_wait_on(): void
    {
        $this->assertEquals('high', $this->notification()->queue);
    }

    #[Test]
    public function it_belongs_in_the_journal_as_a_reminder_that_came_due(): void
    {
        $this->assertEquals(EmailType::ReminderDue, $this->notification()->emailType());
    }

    private function notification(): ReminderDue
    {
        return new ReminderDue(
            reminderName: 'Birthday, Michael Scott',
            recordName: 'Michael Scott',
            sourceDate: '15 March 2027',
            whenItIsAbout: 'It is in seven days.',
        );
    }
}
