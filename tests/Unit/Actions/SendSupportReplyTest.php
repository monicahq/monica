<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\SendSupportReply;
use App\Enums\AdministratorActionEnum;
use App\Enums\SupportRequestStatusEnum;
use App\Jobs\LogAdministratorAction;
use App\Models\Administrator;
use App\Models\SupportRequest;
use App\Models\User;
use App\Notifications\SupportReplySent;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SendSupportReplyTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_answers_the_user_and_waits_on_them(): void
    {
        Queue::fake();
        Notification::fake();
        $administrator = $this->administrator('Jim', 'Halpert');
        $michael = User::factory()->create();
        $supportRequest = SupportRequest::factory()->toHandle()->create(['account_id' => $michael->account_id, 'user_id' => $michael->id]);

        $reply = new SendSupportReply(
            administrator: $administrator,
            supportRequest: $supportRequest,
            body: 'Could you send the file, or the first contact after the 50th?',
        )->execute();

        $this->assertDatabaseHas('support_messages', [
            'id' => $reply->id,
            'author_name' => 'Jim Halpert',
            'kind' => 'reply',
        ]);
        $this->assertEquals(SupportRequestStatusEnum::WaitingOnUser, $supportRequest->refresh()->status);
        $this->assertDatabaseHas('support_status_changes', [
            'support_request_id' => $supportRequest->id,
            'from_status' => 'to_handle',
            'to_status' => 'waiting_on_user',
            'user_name' => 'Jim Halpert',
        ]);
        Notification::assertSentTo($michael, SupportReplySent::class, fn (SupportReplySent $notification): bool => $notification->body === 'Could you send the file, or the first contact after the 50th?');
        Queue::assertPushedOn('low', LogAdministratorAction::class, fn (LogAdministratorAction $job): bool => $job->action === AdministratorActionEnum::SupportReplySent);
    }

    #[Test]
    public function it_keeps_the_reply_exactly_as_it_was_written(): void
    {
        Queue::fake();
        Notification::fake();
        $michael = User::factory()->create();
        $supportRequest = SupportRequest::factory()->toHandle()->create(['account_id' => $michael->account_id, 'user_id' => $michael->id]);

        $reply = new SendSupportReply(
            administrator: $this->administrator('Jim', 'Halpert'),
            supportRequest: $supportRequest,
            body: '**Step 1.** Jim put <b>my stapler</b> in jello, and a < b.',
        )->execute();

        $this->assertDatabaseHas('support_messages', [
            'id' => $reply->id,
            'body' => '**Step 1.** Jim put <b>my stapler</b> in jello, and a < b.',
        ]);
    }

    #[Test]
    public function it_moves_the_request_to_the_status_chosen_with_the_reply(): void
    {
        Queue::fake();
        Notification::fake();
        $supportRequest = SupportRequest::factory()->toHandle()->create();

        new SendSupportReply(
            administrator: $this->administrator('Pam', 'Beesly'),
            supportRequest: $supportRequest,
            body: 'Access is given per vault. A second vault with only that contact does it.',
            statusAfter: SupportRequestStatusEnum::Solved,
        )->execute();

        $this->assertEquals(SupportRequestStatusEnum::Solved, $supportRequest->refresh()->status);
        $this->assertNotNull($supportRequest->solved_at);
    }

    #[Test]
    public function it_refuses_somebody_who_does_not_run_the_instance(): void
    {
        $this->expectException(ModelNotFoundException::class);

        new SendSupportReply(
            administrator: User::factory()->create(),
            supportRequest: SupportRequest::factory()->create(),
            body: 'Dwight here, I can help.',
        )->execute();
    }

    private function administrator(string $firstName, string $lastName): User
    {
        $user = User::factory()->create(['first_name' => $firstName, 'last_name' => $lastName]);
        Administrator::factory()->create(['user_id' => $user->id]);

        return $user;
    }
}
