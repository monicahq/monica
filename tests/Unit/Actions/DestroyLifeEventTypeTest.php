<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\CreateLifeEventType;
use App\Actions\DestroyLifeEventType;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\LifeEventCategory;
use App\Models\LifeEventType;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DestroyLifeEventTypeTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_deletes_a_type_the_product_did_not_supply(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $lifeEventType = $this->lifeEventType($user);

        new DestroyLifeEventType(user: $user, lifeEventType: $lifeEventType)->execute();

        $this->assertSoftDeleted($lifeEventType);
    }

    #[Test]
    public function it_leaves_the_structure_where_it_is_so_a_restore_brings_the_fields_back(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $lifeEventType = $this->lifeEventType($user);
        $structure = $lifeEventType->structure;

        new DestroyLifeEventType(user: $user, lifeEventType: $lifeEventType)->execute();

        $this->assertModelExists($structure);
        $this->assertNotNull($structure->refresh()->published_version_id);
    }

    #[Test]
    public function it_refuses_a_type_from_another_account(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $elsewhere = $this->lifeEventType(User::factory()->create());

        $this->expectException(ModelNotFoundException::class);

        new DestroyLifeEventType(user: $user, lifeEventType: $elsewhere)->execute();
    }

    #[Test]
    public function it_writes_the_deletion_to_the_journal(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        new DestroyLifeEventType(user: $user, lifeEventType: $this->lifeEventType($user))->execute();

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::LifeEventTypeDeletion,
        );
    }

    #[Test]
    public function it_refuses_a_type_the_product_supplied(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $supplied = LifeEventType::factory()->supplied()->create(['account_id' => $user->account_id]);

        $this->expectException(AuthorizationException::class);

        new DestroyLifeEventType(user: $user, lifeEventType: $supplied)->execute();
    }

    private function lifeEventType(User $user): LifeEventType
    {
        return new CreateLifeEventType(
            user: $user,
            lifeEventCategory: LifeEventCategory::factory()->create(['account_id' => $user->account_id]),
            name: 'Restaurant',
        )->execute();
    }
}
