<?php

namespace Tests\Unit\Domains\Settings\ManageNotificationChannels\Services;

use Tests\TestCase;
use App\Models\User;
use App\Models\Account;
use App\Jobs\CreateAuditLog;
use Illuminate\Support\Facades\Queue;
use App\Models\UserNotificationChannel;
use Illuminate\Validation\ValidationException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Settings\ManageNotificationChannels\Services\CreateUserNotificationChannel;
use App\Settings\ManageNotificationChannels\Services\DestroyUserNotificationChannel;

class DestroyUserNotificationChannelTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_deletes_the_channel(): void
    {
        $ross = $this->createUser();
        $channel = UserNotificationChannel::factory()->create([
            'user_id' => $ross->id,
        ]);
        $this->executeService($ross, $ross->account, $channel);
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given(): void
    {
        $request = [
            'title' => 'Ross',
        ];

        $this->expectException(ValidationException::class);
        (new CreateUserNotificationChannel)->execute($request);
    }

    /** @test */
    public function it_fails_if_user_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $ross = $this->createUser();
        $account = $this->createAccount();
        $channel = UserNotificationChannel::factory()->create([
            'user_id' => $ross->id,
        ]);
        $this->executeService($ross, $account, $channel);
    }

    /** @test */
    public function it_fails_if_channel_doesnt_belong_to_user(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $ross = $this->createUser();
        $channel = UserNotificationChannel::factory()->create();
        $this->executeService($ross, $ross->account, $channel);
    }

    private function executeService(User $author, Account $account, UserNotificationChannel $channel): void
    {
        Queue::fake();

        $request = [
            'account_id' => $account->id,
            'author_id' => $author->id,
            'user_notification_channel_id' => $channel->id,
        ];

        (new DestroyUserNotificationChannel)->execute($request);

        $this->assertDatabaseMissing('user_notification_channels', [
            'id' => $channel->id,
        ]);

        Queue::assertPushed(CreateAuditLog::class, function ($job) {
            return $job->auditLog['action_name'] === 'user_notification_channel_destroyed';
        });
    }
}
