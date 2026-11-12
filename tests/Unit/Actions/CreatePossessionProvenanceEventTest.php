<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\CreatePossessionProvenanceEvent;
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

class CreatePossessionProvenanceEventTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_records_a_provenance_event(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $possession = Possession::factory()->create(['vault_id' => $vault->id, 'name' => 'Beet press']);
        $purchase = PossessionTransaction::factory()->create(['possession_id' => $possession->id]);

        $event = new CreatePossessionProvenanceEvent(
            user: $user,
            possession: $possession,
            type: PossessionProvenanceEventTypeEnum::Acquisition,
            datePrecision: PossessionProvenanceDatePrecisionEnum::Month,
            occurredOn: '1921-06',
            title: ' <b>Bought by Grandfather Schrute</b> ',
            from: 'Honesdale Hardware',
            to: 'Dwight Schrute Sr.',
            location: 'Honesdale',
            description: 'Mose remembers the cart it came on.',
            referenceNumber: 'Receipt 12',
            sourceUrl: 'https://schrutefarms.example.com/history',
            transaction: $purchase,
            isVerified: true,
        )->execute();

        $this->assertInstanceOf(PossessionProvenanceEvent::class, $event);
        $this->assertDatabaseHas('possession_provenance_events', [
            'id' => $event->id,
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
            'possession_id' => $possession->id,
            'possession_transaction_id' => $purchase->id,
            'type' => 'acquisition',
            'title' => 'Bought by Grandfather Schrute',
            'date_precision' => 'month',
            'from' => 'Honesdale Hardware',
            'to' => 'Dwight Schrute Sr.',
            'location' => 'Honesdale',
            'reference_number' => 'Receipt 12',
            'source_url' => 'https://schrutefarms.example.com/history',
            'is_verified' => true,
        ]);
        $this->assertEquals('1921-06-01', $event->occurred_on?->toDateString());

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::PossessionProvenanceEventCreation
                && $job->parameters === ['name' => 'Beet press'],
        );
    }

    #[Test]
    public function an_unknown_date_and_an_empty_title_are_stored_as_nothing(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $possession = Possession::factory()->create(['vault_id' => Vault::factory()->create(['account_id' => $user->account_id])->id]);

        $event = new CreatePossessionProvenanceEvent(
            user: $user,
            possession: $possession,
            type: PossessionProvenanceEventTypeEnum::Origin,
            datePrecision: PossessionProvenanceDatePrecisionEnum::Unknown,
            occurredOn: '1921',
            title: '  ',
        )->execute();

        $this->assertNull($event->occurred_on);
        $this->assertNull($event->title);
        $this->assertFalse($event->is_verified);
    }

    #[Test]
    public function it_refuses_a_date_that_does_not_match_its_precision(): void
    {
        $user = User::factory()->create();
        $possession = Possession::factory()->create(['vault_id' => Vault::factory()->create(['account_id' => $user->account_id])->id]);

        try {
            new CreatePossessionProvenanceEvent(
                user: $user,
                possession: $possession,
                type: PossessionProvenanceEventTypeEnum::Origin,
                datePrecision: PossessionProvenanceDatePrecisionEnum::Year,
                occurredOn: '1921-06-01',
            )->execute();

            $this->fail('A day written for a year was accepted.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('occurred_on', $exception->errors());
        }

        $this->assertDatabaseCount('possession_provenance_events', 0);
    }

    #[Test]
    public function it_refuses_a_malformed_source_link(): void
    {
        $user = User::factory()->create();
        $possession = Possession::factory()->create(['vault_id' => Vault::factory()->create(['account_id' => $user->account_id])->id]);

        try {
            new CreatePossessionProvenanceEvent(
                user: $user,
                possession: $possession,
                type: PossessionProvenanceEventTypeEnum::Publication,
                occurredOn: '2007-05-10',
                sourceUrl: 'the Scranton Times',
            )->execute();

            $this->fail('A malformed link was accepted.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('source_url', $exception->errors());
        }
    }

    #[Test]
    public function it_refuses_a_transaction_of_another_possession(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $possession = Possession::factory()->create(['vault_id' => $vault->id]);
        $otherPurchase = PossessionTransaction::factory()->create([
            'possession_id' => Possession::factory()->create(['vault_id' => $vault->id])->id,
        ]);

        try {
            new CreatePossessionProvenanceEvent(
                user: $user,
                possession: $possession,
                type: PossessionProvenanceEventTypeEnum::Acquisition,
                occurredOn: '2005-03-24',
                transaction: $otherPurchase,
            )->execute();

            $this->fail('A transaction of another possession was linked.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('possession_transaction_id', $exception->errors());
        }
    }

    #[Test]
    public function it_refuses_a_possession_of_another_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        new CreatePossessionProvenanceEvent(
            user: User::factory()->create(),
            possession: Possession::factory()->create(),
            type: PossessionProvenanceEventTypeEnum::Origin,
        )->execute();
    }
}
