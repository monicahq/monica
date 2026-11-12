<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\DestroyPossessionInsuranceRecord;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Possession;
use App\Models\PossessionInsuranceRecord;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DestroyPossessionInsuranceRecordTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_deletes_the_record(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $possession = Possession::factory()->create(['vault_id' => Vault::factory()->create(['account_id' => $user->account_id])->id, 'name' => 'Dundie award']);
        $record = PossessionInsuranceRecord::factory()->create(['possession_id' => $possession->id]);

        new DestroyPossessionInsuranceRecord(user: $user, record: $record)->execute();

        $this->assertModelMissing($record);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::PossessionInsuranceRecordDeletion
                && $job->parameters === ['name' => 'Dundie award'],
        );
    }

    #[Test]
    public function it_refuses_a_record_of_another_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        new DestroyPossessionInsuranceRecord(
            user: User::factory()->create(),
            record: PossessionInsuranceRecord::factory()->create(),
        )->execute();
    }
}
