<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\AttachPossessionTransactionDocument;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\File;
use App\Models\Possession;
use App\Models\PossessionTransaction;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AttachPossessionTransactionDocumentTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_attaches_the_upload_to_the_transaction(): void
    {
        Queue::fake();
        Storage::fake('local');

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $sebring = Possession::factory()->create(['vault_id' => $vault->id, 'name' => 'Chrysler Sebring']);
        $purchase = PossessionTransaction::factory()->create(['possession_id' => $sebring->id]);

        $file = new AttachPossessionTransactionDocument(
            user: $user,
            transaction: $purchase,
            upload: UploadedFile::fake()->create('invoice-4021.pdf', 4, 'application/pdf'),
        )->execute();

        $this->assertInstanceOf(File::class, $file);
        $this->assertDatabaseHas('possession_transaction_file', [
            'possession_transaction_id' => $purchase->id,
            'file_id' => $file->id,
        ]);
        $this->assertDatabaseHas('files', [
            'id' => $file->id,
            'vault_id' => $vault->id,
            'original_name' => 'invoice-4021.pdf',
            'reference_count' => 1,
            'orphaned_at' => null,
        ]);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::PossessionTransactionDocumentAddition
                && $job->parameters === ['file' => 'invoice-4021.pdf', 'name' => 'Chrysler Sebring'],
        );
    }

    #[Test]
    public function it_refuses_a_transaction_of_another_account(): void
    {
        $user = User::factory()->create();
        $purchase = PossessionTransaction::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        new AttachPossessionTransactionDocument(user: $user, transaction: $purchase, upload: UploadedFile::fake()->create('scan.pdf', 1))->execute();
    }
}
