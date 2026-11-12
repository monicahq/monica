<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\AttachPossessionValuationDocument;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\File;
use App\Models\Possession;
use App\Models\PossessionValuation;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AttachPossessionValuationDocumentTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_attaches_the_upload_to_the_valuation(): void
    {
        Queue::fake();
        Storage::fake('local');

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $sebring = Possession::factory()->create(['vault_id' => $vault->id, 'name' => 'Chrysler Sebring']);
        $appraisal = PossessionValuation::factory()->create(['possession_id' => $sebring->id]);

        $file = new AttachPossessionValuationDocument(
            user: $user,
            valuation: $appraisal,
            upload: UploadedFile::fake()->create('appraisal-0212.pdf', 4, 'application/pdf'),
        )->execute();

        $this->assertInstanceOf(File::class, $file);
        $this->assertDatabaseHas('possession_valuation_file', [
            'possession_valuation_id' => $appraisal->id,
            'file_id' => $file->id,
        ]);
        $this->assertDatabaseHas('files', [
            'id' => $file->id,
            'vault_id' => $vault->id,
            'original_name' => 'appraisal-0212.pdf',
            'reference_count' => 1,
            'orphaned_at' => null,
        ]);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::PossessionValuationDocumentAddition
                && $job->parameters === ['file' => 'appraisal-0212.pdf', 'name' => 'Chrysler Sebring'],
        );
    }

    #[Test]
    public function it_refuses_a_valuation_of_another_account(): void
    {
        $user = User::factory()->create();
        $appraisal = PossessionValuation::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        new AttachPossessionValuationDocument(user: $user, valuation: $appraisal, upload: UploadedFile::fake()->create('scan.pdf', 1))->execute();
    }
}
