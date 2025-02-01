<?php

namespace Tests\Unit\Domains\Settings\ManageNotificationChannels\Services;

use App\Domains\Settings\ManageNotificationChannels\Services\SendTestTelegramNotification;
use App\Models\User;
use App\Models\UserNotificationChannel;
use App\Notifications\ReminderTriggered;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SendTestTelegramNotificationTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_sends_a_test_telegram_notification(): void
    {
        $ross = $this->createUser();
        $channel = UserNotificationChannel::factory()->create([
            'user_id' => $ross->id,
            'content' => '12344',
            'type' => UserNotificationChannel::TYPE_TELEGRAM,
        ]);
        $this->executeService($ross, $channel);
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given(): void
    {
        $request = [
            'title' => 'Ross',
        ];

        $this->expectException(ValidationException::class);
        (new SendTestTelegramNotification)->execute($request);
    }

    /** @test */
    public function it_fails_if_notification_channel_doesnt_belong_to_user(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $ross = $this->createAdministrator();
        $channel = UserNotificationChannel::factory()->create();
        $this->executeService($ross, $channel);
    }

    /** @test */
    public function it_fails_if_notification_channel_type_is_not_telegram(): void
    {
        $this->expectException(Exception::class);

        $ross = $this->createUser();
        $channel = UserNotificationChannel::factory()->create([
            'user_id' => $ross->id,
            'content' => 'admin@admin.com',
            'type' => 'slack',
        ]);
        $this->executeService($ross, $channel);
    }

    private function executeService(User $author, UserNotificationChannel $channel): void
    {
        Notification::fake();

        $request = [
            'account_id' => $author->account_id,
            'author_id' => $author->id,
            'user_notification_channel_id' => $channel->id,
        ];

        $channel = (new SendTestTelegramNotification)->execute($request);

        $this->assertInstanceOf(
            UserNotificationChannel::class,
            $channel
        );

        Notification::assertSentOnDemand(
            ReminderTriggered::class,
            function ($notification, $channels, $notifiable) {
                return $notifiable->routes['telegram'] == '12344';
            }
        );
    }
}
