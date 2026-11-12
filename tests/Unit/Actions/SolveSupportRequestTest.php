<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\SolveSupportRequest;
use App\Enums\SupportRequestStatusEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\SupportRequest;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SolveSupportRequestTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_marks_the_request_as_solved(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $supportRequest = SupportRequest::factory()->waitingOnUser()->create(['account_id' => $user->account_id]);

        new SolveSupportRequest(user: $user, supportRequest: $supportRequest)->execute();

        $supportRequest->refresh();
        $this->assertEquals(SupportRequestStatusEnum::Solved, $supportRequest->status);
        $this->assertNotNull($supportRequest->solved_at);
        $this->assertDatabaseHas('support_status_changes', [
            'support_request_id' => $supportRequest->id,
            'from_status' => 'waiting_on_user',
            'to_status' => 'solved',
        ]);
        Queue::assertPushedOn('low', LogUserAction::class, fn (LogUserAction $job): bool => $job->action === UserActionEnum::SupportRequestSolved);
    }

    #[Test]
    public function it_refuses_a_request_already_solved(): void
    {
        $user = User::factory()->create();
        $supportRequest = SupportRequest::factory()->solved()->create(['account_id' => $user->account_id]);

        $this->expectException(AuthorizationException::class);

        new SolveSupportRequest(user: $user, supportRequest: $supportRequest)->execute();
    }

    #[Test]
    public function it_refuses_a_request_of_another_account(): void
    {
        $user = User::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        new SolveSupportRequest(user: $user, supportRequest: SupportRequest::factory()->create())->execute();
    }
}
