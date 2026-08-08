<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\ActivateEntityType;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\EntityType;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ActivateEntityTypeTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_allows_records_of_a_kind_to_be_created_again(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $entityType = EntityType::factory()->inactive()->create(['account_id' => $user->account_id]);

        $activated = new ActivateEntityType(user: $user, entityType: $entityType)->execute();

        $this->assertInstanceOf(EntityType::class, $activated);
        $this->assertTrue($entityType->refresh()->is_active);
    }

    #[Test]
    public function it_puts_the_type_back_where_it_was_in_the_navigation(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $entityType = EntityType::factory()->inactive()->create([
            'account_id' => $user->account_id,
            'position' => 4,
        ]);

        new ActivateEntityType(user: $user, entityType: $entityType)->execute();

        $this->assertSame(4, $entityType->refresh()->position);
    }

    #[Test]
    public function it_refuses_a_type_in_another_account(): void
    {
        Queue::fake();

        $michael = User::factory()->create();
        $dwight = User::factory()->create();
        $entityType = EntityType::factory()->inactive()->create(['account_id' => $dwight->account_id]);

        $this->expectException(ModelNotFoundException::class);

        try {
            new ActivateEntityType(user: $michael, entityType: $entityType)->execute();
        } finally {
            $this->assertFalse($entityType->refresh()->is_active);
        }
    }

    #[Test]
    public function it_writes_the_change_to_the_logs(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $entityType = EntityType::factory()->inactive()->create([
            'account_id' => $user->account_id,
            'name_plural' => 'Wine bottles',
        ]);

        new ActivateEntityType(user: $user, entityType: $entityType)->execute();

        Queue::assertPushedOn('low', LogUserAction::class, function (LogUserAction $job): bool {
            return $job->action === UserActionEnum::EntityTypeActivated
                && $job->parameters === ['name' => 'Wine bottles'];
        });
    }
}
