<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\DetachPossessionTransactionDocument;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\File;
use App\Models\Possession;
use App\Models\PossessionTransaction;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DetachPossessionTransactionDocumentTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_lets_go_of_the_document_and_starts_its_grace_period(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $sebring = Possession::factory()->create(['vault_id' => $vault->id, 'name' => 'Chrysler Sebring']);
        $purchase = PossessionTransaction::factory()->create(['possession_id' => $sebring->id]);
        $invoice = File::factory()->used(1)->create(['account_id' => $user->account_id, 'original_name' => 'invoice-4021.pdf']);
        $purchase->documents()->attach($invoice);

        new DetachPossessionTransactionDocument(user: $user, transaction: $purchase, file: $invoice)->execute();

        $this->assertDatabaseMissing('possession_transaction_file', ['possession_transaction_id' => $purchase->id]);
        $this->assertSame(0, $invoice->refresh()->reference_count);
        $this->assertNotNull($invoice->orphaned_at);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::PossessionTransactionDocumentRemoval
                && $job->parameters === ['file' => 'invoice-4021.pdf', 'name' => 'Chrysler Sebring'],
        );
    }

    #[Test]
    public function it_refuses_a_document_of_another_transaction(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $purchase = PossessionTransaction::factory()->create(['possession_id' => Possession::factory()->create(['vault_id' => $vault->id])->id]);
        $shipping = PossessionTransaction::factory()->create(['possession_id' => $purchase->possession_id]);
        $invoice = File::factory()->used(1)->create(['account_id' => $user->account_id]);
        $shipping->documents()->attach($invoice);

        $this->expectException(ModelNotFoundException::class);

        new DetachPossessionTransactionDocument(user: $user, transaction: $purchase, file: $invoice)->execute();
    }

    #[Test]
    public function it_refuses_a_transaction_of_another_account(): void
    {
        $user = User::factory()->create();
        $purchase = PossessionTransaction::factory()->create();
        $invoice = File::factory()->used(1)->create(['account_id' => $purchase->account_id]);
        $purchase->documents()->attach($invoice);

        $this->expectException(ModelNotFoundException::class);

        new DetachPossessionTransactionDocument(user: $user, transaction: $purchase, file: $invoice)->execute();
    }
}
