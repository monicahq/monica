<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\DestroyPossessionTransaction;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\File;
use App\Models\Possession;
use App\Models\PossessionProvenanceEvent;
use App\Models\PossessionTransaction;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DestroyPossessionTransactionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_deletes_the_transaction_and_detaches_its_documents(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $possession = Possession::factory()->create(['vault_id' => Vault::factory()->create(['account_id' => $user->account_id])->id, 'name' => 'Dundie award']);
        $transaction = PossessionTransaction::factory()->create(['possession_id' => $possession->id]);
        $receipt = File::factory()->used(1)->create(['account_id' => $user->account_id]);
        $transaction->documents()->attach($receipt);

        new DestroyPossessionTransaction(user: $user, transaction: $transaction)->execute();

        $this->assertModelMissing($transaction);
        $this->assertDatabaseMissing('possession_transaction_file', ['file_id' => $receipt->id]);
        $this->assertSame(0, $receipt->refresh()->reference_count);
        $this->assertNotNull($receipt->orphaned_at);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::PossessionTransactionDeletion
                && $job->parameters === ['name' => 'Dundie award'],
        );
    }

    #[Test]
    public function a_provenance_event_linked_to_it_stays_without_its_link(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $possession = Possession::factory()->create(['vault_id' => Vault::factory()->create(['account_id' => $user->account_id])->id]);
        $transaction = PossessionTransaction::factory()->create(['possession_id' => $possession->id]);
        $event = PossessionProvenanceEvent::factory()->create([
            'possession_id' => $possession->id,
            'possession_transaction_id' => $transaction->id,
        ]);

        new DestroyPossessionTransaction(user: $user, transaction: $transaction)->execute();

        $this->assertNull($event->refresh()->possession_transaction_id);
    }

    #[Test]
    public function it_refuses_a_transaction_of_another_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        new DestroyPossessionTransaction(
            user: User::factory()->create(),
            transaction: PossessionTransaction::factory()->create(),
        )->execute();
    }
}
