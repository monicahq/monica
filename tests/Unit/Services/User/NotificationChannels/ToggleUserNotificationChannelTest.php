<?php

namespace Tests\Unit\Services\User\NotificationChannels;

use Tests\TestCase;
use App\Models\User;
use App\Jobs\CreateAuditLog;
use Illuminate\Support\Facades\Queue;
use App\Models\UserNotificationChannel;
use Illuminate\Validation\ValidationException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Services\User\NotificationChannels\ToggleUserNotificationChannel;

class ToggleUserNotificationChannelTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_toggles_the_channel(): void
    {
        $ross = $this->createUser();
        $channel = UserNotificationChannel::factory()->create([
            'user_id' => $ross->id,
            'active' => false,
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
        (new ToggleUserNotificationChannel)->execute($request);
    }

    /** @test */
    public function it_fails_if_notification_channel_doesnt_belong_to_user(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $ross = $this->createAdministrator();
        $channel = UserNotificationChannel::factory()->create([
            'active' => false,
        ]);
        $this->executeService($ross, $channel);
    }

    private function executeService(User $author, UserNotificationChannel $channel): void
    {
        Queue::fake();

        $request = [
            'account_id' => $author->account_id,
            'author_id' => $author->id,
            'user_notification_channel_id' => $channel->id,
        ];

        $channel = (new ToggleUserNotificationChannel)->execute($request);

        $this->assertDatabaseHas('user_notification_channels', [
            'id' => $channel->id,
            'user_id' => $author->id,
            'active' => true,
        ]);

        $this->assertInstanceOf(
            UserNotificationChannel::class,
            $channel
        );

        Queue::assertPushed(CreateAuditLog::class, function ($job) {
            return $job->auditLog['action_name'] === 'user_notification_channel_toggled';
        });
    }
}
