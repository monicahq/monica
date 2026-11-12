<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\DestroyPossessionValuation;
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

class DestroyPossessionValuationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_deletes_the_valuation_and_detaches_its_documents(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $possession = Possession::factory()->create(['vault_id' => Vault::factory()->create(['account_id' => $user->account_id])->id, 'name' => 'Dundie award']);
        $valuation = PossessionValuation::factory()->create(['possession_id' => $possession->id]);
        $certificate = File::factory()->used(1)->create(['account_id' => $user->account_id]);
        $valuation->documents()->attach($certificate);

        new DestroyPossessionValuation(user: $user, valuation: $valuation)->execute();

        $this->assertModelMissing($valuation);
        $this->assertDatabaseMissing('possession_valuation_file', ['file_id' => $certificate->id]);
        $this->assertSame(0, $certificate->refresh()->reference_count);
        $this->assertNotNull($certificate->orphaned_at);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::PossessionValuationDeletion
                && $job->parameters === ['name' => 'Dundie award'],
        );
    }

    #[Test]
    public function it_refuses_a_valuation_of_another_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        new DestroyPossessionValuation(
            user: User::factory()->create(),
            valuation: PossessionValuation::factory()->create(),
        )->execute();
    }
}
