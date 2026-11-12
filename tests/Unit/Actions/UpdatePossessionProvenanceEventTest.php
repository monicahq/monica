<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\UpdatePossessionProvenanceEvent;
use App\Enums\PossessionProvenanceDatePrecisionEnum;
use App\Enums\PossessionProvenanceEventTypeEnum;
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
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpdatePossessionProvenanceEventTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_changes_a_provenance_event(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $possession = Possession::factory()->create([
            'vault_id' => Vault::factory()->create(['account_id' => $user->account_id])->id,
            'name' => 'Teapot',
        ]);
        $purchase = PossessionTransaction::factory()->create(['possession_id' => $possession->id]);
        $event = PossessionProvenanceEvent::factory()->create([
            'possession_id' => $possession->id,
            'possession_transaction_id' => $purchase->id,
        ]);

        new UpdatePossessionProvenanceEvent(
            user: $user,
            event: $event,
            type: PossessionProvenanceEventTypeEnum::Gift,
            datePrecision: PossessionProvenanceDatePrecisionEnum::Circa,
            occurredOn: '2005',
            title: 'Given by Pam at Christmas',
            from: 'Pam Beesly',
            to: 'Jim Halpert',
            isVerified: true,
        )->execute();

        $this->assertDatabaseHas('possession_provenance_events', [
            'id' => $event->id,
            'type' => 'gift',
            'date_precision' => 'circa',
            'title' => 'Given by Pam at Christmas',
            'from' => 'Pam Beesly',
            'to' => 'Jim Halpert',
            'possession_transaction_id' => null,
            'is_verified' => true,
        ]);
        $this->assertEquals('2005-01-01', $event->refresh()->occurred_on?->toDateString());

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::PossessionProvenanceEventUpdate
                && $job->parameters === ['name' => 'Teapot'],
        );
    }

    #[Test]
    public function an_unknown_precision_clears_the_date(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $possession = Possession::factory()->create(['vault_id' => Vault::factory()->create(['account_id' => $user->account_id])->id]);
        $event = PossessionProvenanceEvent::factory()->create(['possession_id' => $possession->id]);

        new UpdatePossessionProvenanceEvent(
            user: $user,
            event: $event,
            type: PossessionProvenanceEventTypeEnum::Acquisition,
            datePrecision: PossessionProvenanceDatePrecisionEnum::Unknown,
            occurredOn: '2005-03-24',
        )->execute();

        $this->assertNull($event->refresh()->occurred_on);
    }

    #[Test]
    public function it_refuses_a_date_that_does_not_match_its_precision(): void
    {
        $user = User::factory()->create();
        $possession = Possession::factory()->create(['vault_id' => Vault::factory()->create(['account_id' => $user->account_id])->id]);
        $event = PossessionProvenanceEvent::factory()->create(['possession_id' => $possession->id]);

        $this->expectException(ValidationException::class);

        new UpdatePossessionProvenanceEvent(
            user: $user,
            event: $event,
            type: PossessionProvenanceEventTypeEnum::Acquisition,
            datePrecision: PossessionProvenanceDatePrecisionEnum::Day,
            occurredOn: '2005',
        )->execute();
    }

    #[Test]
    public function it_refuses_an_event_of_another_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        new UpdatePossessionProvenanceEvent(
            user: User::factory()->create(),
            event: PossessionProvenanceEvent::factory()->create(),
            type: PossessionProvenanceEventTypeEnum::Origin,
        )->execute();
    }
}
