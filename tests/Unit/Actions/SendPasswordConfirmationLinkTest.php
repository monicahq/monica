<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\SendPasswordConfirmationLink;
use App\Models\User;
use App\Notifications\PasswordConfirmationRequested;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SendPasswordConfirmationLinkTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_emails_the_link_that_confirms_it_is_them(): void
    {
        Notification::fake();

        $dwight = User::factory()->create(['password' => null]);

        $result = new SendPasswordConfirmationLink(user: $dwight)->execute();

        $this->assertInstanceOf(User::class, $result);

        Notification::assertSentTo(
            $dwight,
            PasswordConfirmationRequested::class,
            fn (PasswordConfirmationRequested $notification): bool => $notification->queue === 'high',
        );
    }
}
