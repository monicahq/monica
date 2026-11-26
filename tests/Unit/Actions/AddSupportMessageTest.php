<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\AddSupportMessage;
use App\Enums\SupportRequestStatusEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Administrator;
use App\Models\SupportRequest;
use App\Models\User;
use App\Notifications\SupportMessageAdded;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AddSupportMessageTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_puts_the_request_back_in_the_queue_of_the_administrators(): void
    {
        Queue::fake();
        $user = User::factory()->create(['first_name' => 'Michael', 'last_name' => 'Scott']);
        $supportRequest = SupportRequest::factory()->waitingOnUser()->create(['account_id' => $user->account_id]);

        $message = new AddSupportMessage(
            user: $user,
            supportRequest: $supportRequest,
            body: 'Settings show New York. I moved to Paris in March.',
        )->execute();

        $this->assertDatabaseHas('support_messages', [
            'id' => $message->id,
            'author_name' => 'Michael Scott',
            'kind' => 'message',
            'body' => 'Settings show New York. I moved to Paris in March.',
        ]);
        $this->assertEquals(SupportRequestStatusEnum::ToHandle, $supportRequest->refresh()->status);
        $this->assertDatabaseHas('support_status_changes', [
            'support_request_id' => $supportRequest->id,
            'from_status' => 'waiting_on_user',
            'to_status' => 'to_handle',
        ]);
        Queue::assertPushedOn('low', LogUserAction::class, fn (LogUserAction $job): bool => $job->action === UserActionEnum::SupportMessageAdded);
    }

    #[Test]
    public function it_keeps_the_message_exactly_as_it_was_written(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $supportRequest = SupportRequest::factory()->waitingOnUser()->create(['account_id' => $user->account_id]);

        $message = new AddSupportMessage(
            user: $user,
            supportRequest: $supportRequest,
            body: '**Step 1.** Jim put <b>my stapler</b> in jello, and a < b.',
        )->execute();

        $this->assertDatabaseHas('support_messages', [
            'id' => $message->id,
            'body' => '**Step 1.** Jim put <b>my stapler</b> in jello, and a < b.',
        ]);
    }

    #[Test]
    public function it_reopens_a_solved_request_under_the_same_number(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $supportRequest = SupportRequest::factory()->solved()->create(['account_id' => $user->account_id, 'number' => 245110]);

        new AddSupportMessage(user: $user, supportRequest: $supportRequest, body: 'Holly still sees the whole vault.')->execute();

        $supportRequest->refresh();
        $this->assertEquals(SupportRequestStatusEnum::ToHandle, $supportRequest->status);
        $this->assertNull($supportRequest->solved_at);
        $this->assertEquals('MON-245110', $supportRequest->reference);
    }

    #[Test]
    public function it_writes_no_history_when_the_request_is_already_waiting_for_support(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $supportRequest = SupportRequest::factory()->toHandle()->create(['account_id' => $user->account_id]);

        new AddSupportMessage(user: $user, supportRequest: $supportRequest, body: 'One more detail: it happens on Fridays.')->execute();

        $this->assertDatabaseCount('support_status_changes', 0);
    }

    #[Test]
    public function it_refuses_a_request_of_another_account(): void
    {
        $user = User::factory()->create();
        $supportRequest = SupportRequest::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        new AddSupportMessage(user: $user, supportRequest: $supportRequest, body: 'Is this Stamford?')->execute();
    }

    #[Test]
    public function it_emails_every_active_administrator_that_the_user_wrote_again(): void
    {
        Queue::fake();
        Notification::fake();

        $jan = User::factory()->create();
        $robert = User::factory()->create(['is_active' => false]);
        Administrator::factory()->create(['user_id' => $jan->id]);
        Administrator::factory()->create(['user_id' => $robert->id]);
        $user = User::factory()->create(['first_name' => 'Michael', 'last_name' => 'Scott']);
        $supportRequest = SupportRequest::factory()->waitingOnUser()->create(['account_id' => $user->account_id, 'number' => 245110]);

        new AddSupportMessage(
            user: $user,
            supportRequest: $supportRequest,
            body: 'Settings show New York. I moved to Paris in March.',
        )->execute();

        Notification::assertSentToTimes($jan, SupportMessageAdded::class, 1);
        Notification::assertSentTo($jan, SupportMessageAdded::class, fn (SupportMessageAdded $notification): bool => $notification->reference === 'MON-245110'
            && $notification->accountId === $user->account_id
            && $notification->authorName === 'Michael Scott'
            && ! $notification->reopened
            && ! $notification->hasAttachments
            && $notification->url === route('admin.support.show', $supportRequest));
        Notification::assertNotSentTo($robert, SupportMessageAdded::class);
    }

    #[Test]
    public function it_tells_the_administrators_that_the_message_reopened_a_solved_request(): void
    {
        Queue::fake();
        Notification::fake();

        $jan = User::factory()->create();
        Administrator::factory()->create(['user_id' => $jan->id]);
        $user = User::factory()->create();
        $supportRequest = SupportRequest::factory()->solved()->create(['account_id' => $user->account_id]);

        new AddSupportMessage(user: $user, supportRequest: $supportRequest, body: 'Holly still sees the whole vault.')->execute();

        Notification::assertSentTo($jan, SupportMessageAdded::class, fn (SupportMessageAdded $notification): bool => $notification->reopened);
    }

    #[Test]
    public function it_does_not_email_the_administrator_who_wrote_the_message(): void
    {
        Queue::fake();
        Notification::fake();

        $jan = User::factory()->create();
        $david = User::factory()->create();
        Administrator::factory()->create(['user_id' => $jan->id]);
        Administrator::factory()->create(['user_id' => $david->id]);
        $supportRequest = SupportRequest::factory()->toHandle()->create(['account_id' => $jan->account_id]);

        new AddSupportMessage(user: $jan, supportRequest: $supportRequest, body: 'One more detail: it happens on Fridays.')->execute();

        Notification::assertNotSentTo($jan, SupportMessageAdded::class);
        Notification::assertSentTo($david, SupportMessageAdded::class);
    }

    #[Test]
    public function it_emails_no_administrator_when_support_is_switched_off(): void
    {
        Queue::fake();
        Notification::fake();
        config(['monica.support_enabled' => false]);

        $jan = User::factory()->create();
        Administrator::factory()->create(['user_id' => $jan->id]);
        $user = User::factory()->create();
        $supportRequest = SupportRequest::factory()->waitingOnUser()->create(['account_id' => $user->account_id]);

        new AddSupportMessage(user: $user, supportRequest: $supportRequest, body: 'Settings show New York.')->execute();

        Notification::assertNothingSent();
    }
}
