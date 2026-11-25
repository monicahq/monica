<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\RestoreRelationship;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Group;
use App\Models\Relationship;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RestoreRelationshipTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_brings_it_back_from_the_trash(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $group = Group::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id]);
        $relationship = Relationship::factory()->create(['account_id' => $user->account_id, 'vault_id' => $group->vault_id, 'deleted_at' => now()]);

        new RestoreRelationship(user: $user, vault: $vault, relationship: $relationship)->execute();

        $this->assertNotSoftDeleted($relationship);

        Queue::assertPushedOn('low', LogUserAction::class, fn (LogUserAction $job): bool => $job->action === UserActionEnum::RelationshipRestoration);
    }

    #[Test]
    public function it_refuses_a_record_of_another_vault(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $group = Group::factory()->create(['account_id' => $user->account_id]);
        $relationship = Relationship::factory()->create(['account_id' => $user->account_id, 'vault_id' => $group->vault_id, 'deleted_at' => now()]);

        $this->expectException(ModelNotFoundException::class);

        new RestoreRelationship(user: $user, vault: $vault, relationship: $relationship)->execute();
    }
}
