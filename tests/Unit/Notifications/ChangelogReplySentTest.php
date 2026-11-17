<?php

declare(strict_types=1);

namespace Tests\Unit\Notifications;

use App\Enums\EmailType;
use App\Models\User;
use App\Notifications\ChangelogReplySent;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ChangelogReplySentTest extends TestCase
{
    #[Test]
    public function it_brings_the_answer_to_a_private_comment_to_its_author(): void
    {
        $notification = new ChangelogReplySent(
            entryTitle: 'Birthdays show the age somebody turns',
            administratorName: 'Jim Halpert',
            body: 'The age follows the year of birth you wrote.',
        );

        $message = $notification->toMail(User::factory()->make(['account_id' => 1]));

        $this->assertEquals('Reply to your comment on What’s new', $message->subject);
        $this->assertEquals(route('changelog.index'), $message->actionUrl);
        $this->assertEquals(['Replying to this email does not reach us. Nobody else sees this reply.'], $message->outroLines);
        $this->assertStringContainsString('The age follows the year of birth you wrote.', (string) $message->render());
        $this->assertEquals(EmailType::ChangelogReplySent, $notification->emailType());
    }
}
