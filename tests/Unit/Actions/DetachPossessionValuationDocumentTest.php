<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\DetachPossessionValuationDocument;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\File;
use App\Models\Possession;
use App\Models\PossessionValuation;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DetachPossessionValuationDocumentTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_lets_go_of_the_document_and_starts_its_grace_period(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $sebring = Possession::factory()->create(['vault_id' => $vault->id, 'name' => 'Chrysler Sebring']);
        $appraisal = PossessionValuation::factory()->create(['possession_id' => $sebring->id]);
        $certificate = File::factory()->used(1)->create(['account_id' => $user->account_id, 'original_name' => 'appraisal-0212.pdf']);
        $appraisal->documents()->attach($certificate);

        new DetachPossessionValuationDocument(user: $user, valuation: $appraisal, file: $certificate)->execute();

        $this->assertDatabaseMissing('possession_valuation_file', ['possession_valuation_id' => $appraisal->id]);
        $this->assertSame(0, $certificate->refresh()->reference_count);
        $this->assertNotNull($certificate->orphaned_at);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::PossessionValuationDocumentRemoval
                && $job->parameters === ['file' => 'appraisal-0212.pdf', 'name' => 'Chrysler Sebring'],
        );
    }

    #[Test]
    public function it_refuses_a_document_of_another_valuation(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $appraisal = PossessionValuation::factory()->create(['possession_id' => Possession::factory()->create(['vault_id' => $vault->id])->id]);
        $listing = PossessionValuation::factory()->create(['possession_id' => $appraisal->possession_id]);
        $certificate = File::factory()->used(1)->create(['account_id' => $user->account_id]);
        $listing->documents()->attach($certificate);

        $this->expectException(ModelNotFoundException::class);

        new DetachPossessionValuationDocument(user: $user, valuation: $appraisal, file: $certificate)->execute();
    }

    #[Test]
    public function it_refuses_a_valuation_of_another_account(): void
    {
        $user = User::factory()->create();
        $appraisal = PossessionValuation::factory()->create();
        $certificate = File::factory()->used(1)->create(['account_id' => $appraisal->account_id]);
        $appraisal->documents()->attach($certificate);

        $this->expectException(ModelNotFoundException::class);

        new DetachPossessionValuationDocument(user: $user, valuation: $appraisal, file: $certificate)->execute();
    }
}
