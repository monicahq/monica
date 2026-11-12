<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\DestroyPossessionMaintenanceRecord;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Possession;
use App\Models\PossessionMaintenanceRecord;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DestroyPossessionMaintenanceRecordTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_deletes_a_job(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $possession = Possession::factory()->create(['vault_id' => Vault::factory()->create(['account_id' => $user->account_id])->id, 'name' => 'Beet press']);
        $record = PossessionMaintenanceRecord::factory()->create(['possession_id' => $possession->id]);

        new DestroyPossessionMaintenanceRecord(user: $user, record: $record)->execute();

        $this->assertModelMissing($record);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::PossessionMaintenanceRecordDeletion
                && $job->parameters === ['name' => 'Beet press'],
        );
    }

    #[Test]
    public function it_refuses_a_record_of_another_account(): void
    {
        $user = User::factory()->create();
        $record = PossessionMaintenanceRecord::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        new DestroyPossessionMaintenanceRecord(user: $user, record: $record)->execute();
    }
}
