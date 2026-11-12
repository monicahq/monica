<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\UpdatePossession;
use App\Enums\CurrencyEnum;
use App\Enums\PossessionFieldKindEnum;
use App\Enums\PossessionStatusEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Location;
use App\Models\Possession;
use App\Models\PossessionField;
use App\Models\PossessionFieldValue;
use App\Models\PossessionLocationStay;
use App\Models\PossessionTag;
use App\Models\User;
use App\Models\Vault;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpdatePossessionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_changes_what_is_known_of_the_possession(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $possession = Possession::factory()->create(['vault_id' => $vault->id, 'name' => 'Sebring']);
        $warehouse = Location::factory()->create(['account_id' => $user->account_id]);

        new UpdatePossession(
            user: $user,
            possession: $possession,
            name: 'Chrysler Sebring',
            description: 'Sold to pay for the condo',
            location: $warehouse,
            identifier: '1C3EL55R',
            status: PossessionStatusEnum::Sold,
            quantity: 1,
        )->execute();

        $this->assertDatabaseHas('possessions', [
            'id' => $possession->id,
            'name' => 'Chrysler Sebring',
            'description' => 'Sold to pay for the condo',
            'current_location_id' => $warehouse->id,
            'identifier' => '1C3EL55R',
            'status' => 'sold',
        ]);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::PossessionUpdate
                && $job->parameters === ['name' => 'Chrysler Sebring'],
        );
    }

    #[Test]
    public function changing_the_location_moves_it_today_and_clearing_it_closes_the_open_stay(): void
    {
        Queue::fake();
        $this->travelTo(CarbonImmutable::parse('2026-11-12 10:00'));

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $office = Location::factory()->create(['account_id' => $user->account_id]);
        $condo = Location::factory()->create(['account_id' => $user->account_id]);
        $possession = Possession::factory()->create(['vault_id' => $vault->id, 'current_location_id' => $office->id]);
        $first = PossessionLocationStay::factory()->create(['possession_id' => $possession->id, 'location_id' => $office->id, 'arrived_on' => '2024-06-04']);

        $this->updateLocation($user, $possession, $office);

        $this->assertCount(1, $possession->locationStays()->get());

        $this->updateLocation($user, $possession, $condo);

        $this->assertEquals('2026-11-12', $first->refresh()->left_on->toDateString());
        $this->assertEquals($condo->id, $possession->openLocationStay()->first()->location_id);
        $this->assertEquals($condo->id, $possession->refresh()->current_location_id);

        $this->updateLocation($user, $possession, null);

        $this->assertNull($possession->openLocationStay()->first());
        $this->assertNull($possession->refresh()->current_location_id);
    }

    #[Test]
    public function it_places_a_possession_becoming_featured_last(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        Possession::factory()->featured(1)->create(['vault_id' => $vault->id]);
        $mug = Possession::factory()->create(['vault_id' => $vault->id]);

        new UpdatePossession(
            user: $user,
            possession: $mug,
            name: 'World’s Best Boss mug',
            isFeatured: true,
        )->execute();

        $this->assertSame(2, $mug->refresh()->position);
    }

    #[Test]
    public function it_closes_the_order_behind_a_possession_no_longer_featured(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $sebring = Possession::factory()->featured(1)->create(['vault_id' => $vault->id]);
        $mug = Possession::factory()->featured(2)->create(['vault_id' => $vault->id]);

        new UpdatePossession(
            user: $user,
            possession: $sebring,
            name: 'Chrysler Sebring',
            isFeatured: false,
        )->execute();

        $this->assertNull($sebring->refresh()->position);
        $this->assertSame(1, $mug->refresh()->position);
    }

    #[Test]
    public function it_refuses_a_name_that_holds_nothing(): void
    {
        $user = User::factory()->create();
        $possession = Possession::factory()->create([
            'vault_id' => Vault::factory()->create(['account_id' => $user->account_id])->id,
        ]);

        $this->expectException(ValidationException::class);

        new UpdatePossession(user: $user, possession: $possession, name: '   ')->execute();
    }

    #[Test]
    public function it_refuses_a_possession_of_another_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        new UpdatePossession(
            user: User::factory()->create(),
            possession: Possession::factory()->create(),
            name: 'Chrysler Sebring',
        )->execute();
    }

    #[Test]
    public function it_changes_its_declared_value_and_clears_it_when_left_out(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $possession = Possession::factory()->create(['vault_id' => $vault->id]);

        new UpdatePossession(
            user: $user,
            possession: $possession,
            name: 'Wedding gift',
            declaredValue: '80',
            declaredValueCurrency: CurrencyEnum::Euro,
        )->execute();

        $this->assertDatabaseHas('possessions', ['id' => $possession->id, 'declared_value' => '80.00', 'declared_value_currency' => 'eur']);

        new UpdatePossession(user: $user, possession: $possession, name: 'Wedding gift')->execute();

        $this->assertDatabaseHas('possessions', ['id' => $possession->id, 'declared_value' => null, 'declared_value_currency' => null]);
    }

    #[Test]
    public function it_leaves_the_tags_when_none_are_given_and_replaces_them_otherwise(): void
    {
        $user = User::factory()->create();
        $possession = Possession::factory()->create(['vault_id' => Vault::factory()->create(['account_id' => $user->account_id])->id]);
        $possession->tags()->attach(PossessionTag::factory()->create(['account_id' => $user->account_id, 'name' => 'insured']));

        new UpdatePossession(user: $user, possession: $possession, name: 'Chili pot')->execute();

        $this->assertEquals(['insured'], $possession->fresh()->tags->pluck('name')->all());

        new UpdatePossession(user: $user, possession: $possession, name: 'Chili pot', tagNames: ['office party'])->execute();

        $this->assertEquals(['office party'], $possession->fresh()->tags->pluck('name')->all());
    }

    #[Test]
    public function it_writes_and_clears_the_values_of_its_fields_and_leaves_them_when_none_are_given(): void
    {
        $user = User::factory()->create();
        $sebring = Possession::factory()->create(['vault_id' => Vault::factory()->create(['account_id' => $user->account_id])->id, 'name' => 'Chrysler Sebring']);
        $mileage = PossessionField::factory()->create(['possession_type_id' => $sebring->possession_type_id, 'name' => 'Mileage', 'kind' => PossessionFieldKindEnum::Number]);
        $plate = PossessionField::factory()->create(['possession_type_id' => $sebring->possession_type_id, 'name' => 'Plate', 'kind' => PossessionFieldKindEnum::Text]);
        PossessionFieldValue::factory()->create(['account_id' => $user->account_id, 'possession_id' => $sebring->id, 'possession_field_id' => $plate->id, 'value' => 'PA-1234']);

        new UpdatePossession(user: $user, possession: $sebring, name: 'Chrysler Sebring')->execute();

        $this->assertDatabaseHas('possession_field_values', ['possession_id' => $sebring->id, 'possession_field_id' => $plate->id, 'value' => 'PA-1234']);

        new UpdatePossession(
            user: $user,
            possession: $sebring,
            name: 'Chrysler Sebring',
            values: [$mileage->uuid => '121500', $plate->uuid => ''],
        )->execute();

        $this->assertDatabaseHas('possession_field_values', ['possession_id' => $sebring->id, 'possession_field_id' => $mileage->id, 'value' => '121500']);
        $this->assertDatabaseMissing('possession_field_values', ['possession_id' => $sebring->id, 'possession_field_id' => $plate->id]);
    }

    #[Test]
    public function it_saves_nothing_when_a_value_does_not_fit(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $sebring = Possession::factory()->create(['vault_id' => Vault::factory()->create(['account_id' => $user->account_id])->id, 'name' => 'Sebring']);
        $mileage = PossessionField::factory()->create(['possession_type_id' => $sebring->possession_type_id, 'name' => 'Mileage', 'kind' => PossessionFieldKindEnum::Number]);

        try {
            new UpdatePossession(
                user: $user,
                possession: $sebring,
                name: 'Chrysler Sebring',
                values: [$mileage->uuid => 'banana'],
            )->execute();

            $this->fail('A value that does not fit its field was accepted.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey("values.{$mileage->uuid}", $exception->errors());
        }

        $this->assertDatabaseHas('possessions', ['id' => $sebring->id, 'name' => 'Sebring']);
        $this->assertDatabaseMissing('possession_field_values', ['possession_id' => $sebring->id]);
        Queue::assertNothingPushed();
    }

    private function updateLocation(User $user, Possession $possession, ?Location $location): void
    {
        new UpdatePossession(
            user: $user,
            possession: $possession->refresh(),
            name: $possession->name,
            location: $location,
            status: $possession->status,
            quantity: $possession->quantity,
        )->execute();
    }
}
