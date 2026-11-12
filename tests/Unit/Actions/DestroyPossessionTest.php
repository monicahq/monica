<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\DestroyPossession;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\File;
use App\Models\Possession;
use App\Models\PossessionFieldValue;
use App\Models\PossessionTransaction;
use App\Models\PossessionValuation;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DestroyPossessionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_deletes_the_possession_with_its_values_and_closes_the_featured_order(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $sebring = Possession::factory()->featured(1)->create(['vault_id' => $vault->id, 'name' => 'Chrysler Sebring']);
        $mug = Possession::factory()->featured(2)->create(['vault_id' => $vault->id]);
        $plate = PossessionFieldValue::factory()->create(['possession_id' => $sebring->id]);

        new DestroyPossession(user: $user, possession: $sebring)->execute();

        $this->assertModelMissing($sebring);
        $this->assertModelMissing($plate);
        $this->assertSame(1, $mug->refresh()->position);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::PossessionDeletion
                && $job->parameters === ['name' => 'Chrysler Sebring'],
        );
    }

    #[Test]
    public function it_detaches_the_documents_of_its_transactions(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $possession = Possession::factory()->create(['vault_id' => Vault::factory()->create(['account_id' => $user->account_id])->id]);
        $transaction = PossessionTransaction::factory()->create(['possession_id' => $possession->id]);
        $receipt = File::factory()->used(1)->create(['account_id' => $user->account_id]);
        $transaction->documents()->attach($receipt);

        new DestroyPossession(user: $user, possession: $possession)->execute();

        $this->assertModelMissing($transaction);
        $this->assertSame(0, $receipt->refresh()->reference_count);
        $this->assertNotNull($receipt->orphaned_at);
    }

    #[Test]
    public function it_detaches_the_documents_of_its_valuations(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $possession = Possession::factory()->create(['vault_id' => Vault::factory()->create(['account_id' => $user->account_id])->id]);
        $valuation = PossessionValuation::factory()->create(['possession_id' => $possession->id]);
        $certificate = File::factory()->used(1)->create(['account_id' => $user->account_id]);
        $valuation->documents()->attach($certificate);

        new DestroyPossession(user: $user, possession: $possession)->execute();

        $this->assertModelMissing($valuation);
        $this->assertSame(0, $certificate->refresh()->reference_count);
        $this->assertNotNull($certificate->orphaned_at);
    }

    #[Test]
    public function it_refuses_a_possession_of_another_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        new DestroyPossession(
            user: User::factory()->create(),
            possession: Possession::factory()->create(),
        )->execute();
    }
}
