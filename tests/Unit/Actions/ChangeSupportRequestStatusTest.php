<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\ChangeSupportRequestStatus;
use App\Enums\AdministratorActionEnum;
use App\Enums\SupportRequestStatusEnum;
use App\Jobs\LogAdministratorAction;
use App\Models\Administrator;
use App\Models\SupportRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ChangeSupportRequestStatusTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_moves_the_request_and_writes_down_who_moved_it(): void
    {
        Queue::fake();
        $administrator = User::factory()->create(['first_name' => 'Pam', 'last_name' => 'Beesly']);
        Administrator::factory()->create(['user_id' => $administrator->id]);
        $supportRequest = SupportRequest::factory()->toHandle()->create();

        new ChangeSupportRequestStatus(
            administrator: $administrator,
            supportRequest: $supportRequest,
            status: SupportRequestStatusEnum::Solved,
        )->execute();

        $this->assertEquals(SupportRequestStatusEnum::Solved, $supportRequest->refresh()->status);
        $this->assertNotNull($supportRequest->solved_at);
        $this->assertDatabaseHas('support_status_changes', [
            'support_request_id' => $supportRequest->id,
            'from_status' => 'to_handle',
            'to_status' => 'solved',
            'user_name' => 'Pam Beesly',
        ]);
        Queue::assertPushedOn('low', LogAdministratorAction::class, fn (LogAdministratorAction $job): bool => $job->action === AdministratorActionEnum::SupportRequestStatusChanged);
    }

    #[Test]
    public function it_changes_nothing_when_the_status_is_the_same(): void
    {
        Queue::fake();
        $administrator = User::factory()->create();
        Administrator::factory()->create(['user_id' => $administrator->id]);
        $supportRequest = SupportRequest::factory()->waitingOnUser()->create();

        new ChangeSupportRequestStatus(administrator: $administrator, supportRequest: $supportRequest, status: SupportRequestStatusEnum::WaitingOnUser)->execute();

        $this->assertDatabaseCount('support_status_changes', 0);
        Queue::assertNotPushed(LogAdministratorAction::class);
    }

    #[Test]
    public function it_refuses_somebody_who_does_not_run_the_instance(): void
    {
        $this->expectException(ModelNotFoundException::class);

        new ChangeSupportRequestStatus(
            administrator: User::factory()->create(),
            supportRequest: SupportRequest::factory()->create(),
            status: SupportRequestStatusEnum::Solved,
        )->execute();
    }
}
