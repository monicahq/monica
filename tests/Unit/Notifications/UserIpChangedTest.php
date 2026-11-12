<?php

declare(strict_types=1);

namespace Tests\Unit\Notifications;

use App\Enums\EmailType;
use App\Models\User;
use App\Notifications\UserIpChanged;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UserIpChangedTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_names_the_account_and_where_the_sign_in_came_from(): void
    {
        $michael = User::factory()->create();

        $message = new UserIpChanged(email: 'michael.scott@dundermifflin.com', ip: '10.0.0.1')
            ->toMail($michael);

        $rendered = (string) $message->render();

        $this->assertStringContainsString('michael.scott@dundermifflin.com', $rendered);
        $this->assertStringContainsString('10.0.0.1', $rendered);
    }

    #[Test]
    public function it_boxes_when_and_where_it_happened(): void
    {
        $michael = User::factory()->create();

        $message = new UserIpChanged(email: 'michael.scott@dundermifflin.com', ip: '10.0.0.1')
            ->toMail($michael);

        $this->assertStringContainsString('Where: 10.0.0.1', (string) $message->panel);
    }

    #[Test]
    public function it_goes_out_as_an_email(): void
    {
        $michael = User::factory()->create();

        $notification = new UserIpChanged(email: 'michael.scott@dundermifflin.com', ip: '10.0.0.1');

        $this->assertEquals(['mail'], $notification->via($michael));
    }

    #[Test]
    public function it_belongs_in_the_journal_as_a_sign_in_from_a_new_place(): void
    {
        $notification = new UserIpChanged(email: 'michael.scott@dundermifflin.com', ip: '10.0.0.1');

        $this->assertEquals(EmailType::UserIpChanged, $notification->emailType());
    }
}
