<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\AddGroupFile;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Group;
use App\Models\GroupFile;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AddGroupFileTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_keeps_the_file_with_the_group(): void
    {
        Queue::fake();
        Storage::fake('local');

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $group = Group::factory()->create(['vault_id' => $vault->id, 'name' => 'Finer things club']);

        $attachment = new AddGroupFile(
            user: $user,
            vault: $vault,
            group: $group,
            upload: UploadedFile::fake()->create('reading-list.pdf', 4, 'application/pdf'),
        )->execute();

        $this->assertInstanceOf(GroupFile::class, $attachment);
        $this->assertDatabaseHas('group_files', [
            'id' => $attachment->id,
            'group_id' => $group->id,
            'account_id' => $user->account_id,
            'added_by_user_id' => $user->id,
        ]);
        $this->assertDatabaseHas('files', [
            'id' => $attachment->file_id,
            'vault_id' => $vault->id,
            'original_name' => 'reading-list.pdf',
            'reference_count' => 1,
            'orphaned_at' => null,
        ]);

        Queue::assertPushedOn('low', LogUserAction::class, fn (LogUserAction $job): bool => $job->action === UserActionEnum::GroupFileAddition);
    }

    #[Test]
    public function it_refuses_a_group_of_another_vault(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $group = Group::factory()->create(['vault_id' => Vault::factory()->create(['account_id' => $user->account_id])->id]);

        $this->expectException(ModelNotFoundException::class);

        new AddGroupFile(user: $user, vault: $vault, group: $group, upload: UploadedFile::fake()->create('scan.pdf', 1))->execute();
    }

    #[Test]
    public function it_refuses_a_group_of_another_account(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $this->expectException(ModelNotFoundException::class);

        new AddGroupFile(user: $user, vault: $vault, group: Group::factory()->create(), upload: UploadedFile::fake()->create('scan.pdf', 1))->execute();
    }
}
