<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\RemoveGroupFile;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Group;
use App\Models\GroupFile;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RemoveGroupFileTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_sends_the_attachment_to_the_trash_and_keeps_the_file(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $group = Group::factory()->create(['vault_id' => $vault->id]);
        $attachment = GroupFile::factory()->create(['group_id' => $group->id]);

        new RemoveGroupFile(user: $user, vault: $vault, group: $group, attachment: $attachment)->execute();

        $this->assertSoftDeleted($attachment);
        $file = $attachment->file->refresh();
        $this->assertEquals(1, $file->reference_count);
        $this->assertNull($file->orphaned_at);

        Queue::assertPushedOn('low', LogUserAction::class, fn (LogUserAction $job): bool => $job->action === UserActionEnum::GroupFileRemoval);
    }

    #[Test]
    public function it_refuses_a_file_kept_with_another_group(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $group = Group::factory()->create(['vault_id' => $vault->id]);
        $attachment = GroupFile::factory()->create(['group_id' => Group::factory()->create(['vault_id' => $vault->id])->id]);

        $this->expectException(ModelNotFoundException::class);

        new RemoveGroupFile(user: $user, vault: $vault, group: $group, attachment: $attachment)->execute();
    }

    #[Test]
    public function it_refuses_a_group_of_another_account(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $attachment = GroupFile::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        new RemoveGroupFile(user: $user, vault: $vault, group: $attachment->group, attachment: $attachment)->execute();
    }
}
