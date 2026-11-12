<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\DestroyPossessionField;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\PossessionField;
use App\Models\PossessionType;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DestroyPossessionFieldTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_deletes_a_field_and_logs_its_name_and_type(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $possessionType = PossessionType::factory()->create(['account_id' => $user->account_id, 'name' => 'Office equipment']);
        $field = PossessionField::factory()->create(['possession_type_id' => $possessionType->id, 'name' => 'Serial number']);

        new DestroyPossessionField(
            user: $user,
            field: $field,
        )->execute();

        $this->assertModelMissing($field);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::PossessionFieldDeletion
                && $job->parameters === ['name' => 'Serial number', 'type' => 'Office equipment'],
        );
    }

    #[Test]
    public function it_refuses_a_field_of_another_account(): void
    {
        $user = User::factory()->create();
        $field = PossessionField::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        new DestroyPossessionField(
            user: $user,
            field: $field,
        )->execute();
    }
}
