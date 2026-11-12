<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\DestroyPossessionProvenanceEvent;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
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

class DestroyPossessionProvenanceEventTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_deletes_an_event_and_keeps_its_transaction(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $possession = Possession::factory()->create([
            'vault_id' => Vault::factory()->create(['account_id' => $user->account_id])->id,
            'name' => 'World’s Best Boss mug',
        ]);
        $purchase = PossessionTransaction::factory()->create(['possession_id' => $possession->id]);
        $event = PossessionProvenanceEvent::factory()->create([
            'possession_id' => $possession->id,
            'possession_transaction_id' => $purchase->id,
        ]);

        new DestroyPossessionProvenanceEvent(user: $user, event: $event)->execute();

        $this->assertModelMissing($event);
        $this->assertModelExists($purchase);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::PossessionProvenanceEventDeletion
                && $job->parameters === ['name' => 'World’s Best Boss mug'],
        );
    }

    #[Test]
    public function it_refuses_an_event_of_another_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        new DestroyPossessionProvenanceEvent(
            user: User::factory()->create(),
            event: PossessionProvenanceEvent::factory()->create(),
        )->execute();
    }
}
