<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\EmptyVaultTrash;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Contact;
use App\Models\Group;
use App\Models\GroupNote;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EmptyVaultTrashTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_deletes_everything_in_the_trash_of_the_vault_for_good(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $otherVault = Vault::factory()->create(['account_id' => $user->account_id]);
        $toby = Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id, 'deleted_at' => now()]);
        $note = GroupNote::factory()->create(['group_id' => Group::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id])->id, 'deleted_at' => now()]);
        $elsewhere = Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $otherVault->id, 'deleted_at' => now()]);

        new EmptyVaultTrash(user: $user, vault: $vault)->execute();

        $this->assertModelMissing($toby);
        $this->assertModelMissing($note);
        $this->assertSoftDeleted($elsewhere);

        Queue::assertPushedOn('low', LogUserAction::class, fn (LogUserAction $job): bool => $job->action === UserActionEnum::TrashEmptied);
        Queue::assertNotPushed(LogUserAction::class, fn (LogUserAction $job): bool => $job->action === UserActionEnum::TrashItemDestruction);
    }
}
