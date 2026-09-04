<?php

declare(strict_types=1);

namespace Tests\Unit\Mail;

use App\Mail\ReminderDue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ReminderDueTest extends TestCase
{
    #[Test]
    public function it_takes_its_subject_from_what_the_reminder_says(): void
    {
        $mail = new ReminderDue(
            reminderName: 'Birthday, Michael Scott',
            recordName: 'Michael Scott',
            sourceDate: '15 March 2027',
            whenItIsAbout: 'It is in seven days.',
        );

        $this->assertSame('Birthday, Michael Scott', $mail->envelope()->subject);
    }

    #[Test]
    public function it_names_the_record_the_date_and_how_far_off_it_is(): void
    {
        $rendered = new ReminderDue(
            reminderName: 'Birthday, Michael Scott',
            recordName: 'Michael Scott',
            sourceDate: '15 March 2027',
            whenItIsAbout: 'It is in seven days.',
        )->render();

        $this->assertStringContainsString('Birthday, Michael Scott', $rendered);
        $this->assertStringContainsString('Michael Scott', $rendered);
        $this->assertStringContainsString('15 March 2027', $rendered);
        $this->assertStringContainsString('It is in seven days.', $rendered);
    }
}
