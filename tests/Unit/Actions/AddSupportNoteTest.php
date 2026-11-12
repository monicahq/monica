<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\AddSupportNote;
use App\Enums\AdministratorActionEnum;
use App\Enums\SupportRequestStatusEnum;
use App\Jobs\LogAdministratorAction;
use App\Models\Administrator;
use App\Models\SupportRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AddSupportNoteTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_leaves_a_note_nobody_but_the_administrators_reads(): void
    {
        Notification::fake();
        Queue::fake();
        $administrator = User::factory()->create(['first_name' => 'Oscar', 'last_name' => 'Martinez']);
        Administrator::factory()->create(['user_id' => $administrator->id]);
        $supportRequest = SupportRequest::factory()->toHandle()->create();

        $note = new AddSupportNote(
            administrator: $administrator,
            supportRequest: $supportRequest,
            body: 'Postmark shows two soft bounces for free.fr this week.',
        )->execute();

        $this->assertDatabaseHas('support_messages', [
            'id' => $note->id,
            'kind' => 'note',
            'author_name' => 'Oscar Martinez',
        ]);
        $this->assertEquals(SupportRequestStatusEnum::ToHandle, $supportRequest->refresh()->status);
        $this->assertDatabaseCount('support_status_changes', 0);
        Notification::assertNothingSent();
        Queue::assertPushedOn('low', LogAdministratorAction::class, fn (LogAdministratorAction $job): bool => $job->action === AdministratorActionEnum::SupportNoteAdded);
    }

    #[Test]
    public function it_refuses_somebody_who_does_not_run_the_instance(): void
    {
        $this->expectException(ModelNotFoundException::class);

        new AddSupportNote(
            administrator: User::factory()->create(),
            supportRequest: SupportRequest::factory()->create(),
            body: 'Dwight was here.',
        )->execute();
    }
}
