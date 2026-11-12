<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\DestroyPossessionType;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\File;
use App\Models\Possession;
use App\Models\PossessionField;
use App\Models\PossessionFieldGroup;
use App\Models\PossessionFieldValue;
use App\Models\PossessionTransaction;
use App\Models\PossessionType;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DestroyPossessionTypeTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_deletes_a_type_with_its_groups_and_fields(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $possessionType = PossessionType::factory()->create(['account_id' => $user->account_id, 'name' => 'Vehicle']);
        $group = PossessionFieldGroup::factory()->create(['possession_type_id' => $possessionType->id]);
        $groupedField = PossessionField::factory()->inGroup($group)->create();
        $standaloneField = PossessionField::factory()->create(['possession_type_id' => $possessionType->id]);

        new DestroyPossessionType(
            user: $user,
            possessionType: $possessionType,
        )->execute();

        $this->assertModelMissing($possessionType);
        $this->assertModelMissing($group);
        $this->assertModelMissing($groupedField);
        $this->assertModelMissing($standaloneField);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::PossessionTypeDeletion
                && $job->parameters === ['name' => 'Vehicle'],
        );
    }

    #[Test]
    public function it_deletes_the_possessions_of_the_type_counts_them_and_closes_the_featured_order(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $car = PossessionType::factory()->create(['account_id' => $user->account_id, 'name' => 'Car']);
        $sebring = Possession::factory()->featured(1)->create(['vault_id' => $vault->id, 'possession_type_id' => $car->id]);
        $plate = PossessionFieldValue::factory()->create(['possession_id' => $sebring->id]);
        Possession::factory()->create(['vault_id' => $vault->id, 'possession_type_id' => $car->id]);
        $mug = Possession::factory()->featured(2)->create(['vault_id' => $vault->id]);

        $count = new DestroyPossessionType(
            user: $user,
            possessionType: $car,
        )->execute();

        $this->assertSame(2, $count);
        $this->assertModelMissing($sebring);
        $this->assertModelMissing($plate);
        $this->assertSame(1, $mug->refresh()->position);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::PossessionsDeletedWithType
                && $job->parameters === ['name' => 'Car', 'count' => 2],
        );
    }

    #[Test]
    public function it_refuses_a_type_of_another_account(): void
    {
        $user = User::factory()->create();
        $possessionType = PossessionType::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        new DestroyPossessionType(
            user: $user,
            possessionType: $possessionType,
        )->execute();
    }

    #[Test]
    public function it_detaches_the_documents_of_the_transactions_of_its_possessions(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $type = PossessionType::factory()->create(['account_id' => $user->account_id]);
        $possession = Possession::factory()->create([
            'vault_id' => Vault::factory()->create(['account_id' => $user->account_id])->id,
            'possession_type_id' => $type->id,
        ]);
        $transaction = PossessionTransaction::factory()->create(['possession_id' => $possession->id]);
        $receipt = File::factory()->used(1)->create(['account_id' => $user->account_id]);
        $transaction->documents()->attach($receipt);

        new DestroyPossessionType(user: $user, possessionType: $type)->execute();

        $this->assertModelMissing($transaction);
        $this->assertSame(0, $receipt->refresh()->reference_count);
    }
}
