<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\CreatePossession;
use App\Enums\CurrencyEnum;
use App\Enums\PossessionFieldKindEnum;
use App\Enums\PossessionStatusEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Location;
use App\Models\Possession;
use App\Models\PossessionField;
use App\Models\PossessionTag;
use App\Models\PossessionType;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CreatePossessionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_records_an_owned_possession_counting_one_and_not_featured(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $car = PossessionType::factory()->create(['account_id' => $user->account_id, 'name' => 'Car']);

        $possession = new CreatePossession(
            user: $user,
            vault: $vault,
            possessionType: $car,
            name: '  Chrysler Sebring  ',
        )->execute();

        $this->assertInstanceOf(Possession::class, $possession);
        $this->assertDatabaseHas('possessions', [
            'id' => $possession->id,
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
            'possession_type_id' => $car->id,
            'current_location_id' => null,
            'name' => 'Chrysler Sebring',
            'status' => 'owned',
            'quantity' => 1,
            'is_featured' => false,
            'position' => null,
        ]);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::PossessionCreation
                && $job->parameters === ['name' => 'Chrysler Sebring'],
        );
    }

    #[Test]
    public function it_records_everything_given(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $car = PossessionType::factory()->create(['account_id' => $user->account_id]);
        $parkingLot = Location::factory()->create(['account_id' => $user->account_id, 'name' => 'Scranton parking lot']);

        $possession = new CreatePossession(
            user: $user,
            vault: $vault,
            possessionType: $car,
            name: 'Chrysler Sebring',
            description: 'The convertible',
            location: $parkingLot,
            identifier: '1C3EL55R',
            status: PossessionStatusEnum::Sold,
            quantity: 2,
        )->execute();

        $this->assertDatabaseHas('possessions', [
            'id' => $possession->id,
            'current_location_id' => $parkingLot->id,
            'description' => 'The convertible',
            'identifier' => '1C3EL55R',
            'status' => 'sold',
            'quantity' => 2,
        ]);
        $this->assertEquals($parkingLot->id, $possession->openLocationStay->location_id);
        $this->assertEquals(now()->toDateString(), $possession->openLocationStay->arrived_on->toDateString());
    }

    #[Test]
    public function it_places_a_featured_possession_last_among_the_featured_ones(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        Possession::factory()->featured(1)->create(['vault_id' => $vault->id]);
        Possession::factory()->featured(2)->create(['vault_id' => $vault->id]);
        $mugs = PossessionType::factory()->create(['account_id' => $user->account_id]);

        $possession = new CreatePossession(
            user: $user,
            vault: $vault,
            possessionType: $mugs,
            name: 'World’s Best Boss mug',
            isFeatured: true,
        )->execute();

        $this->assertSame(3, $possession->refresh()->position);
    }

    #[Test]
    public function it_refuses_a_name_that_holds_nothing_but_markup(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $car = PossessionType::factory()->create(['account_id' => $user->account_id]);

        $this->expectException(ValidationException::class);

        new CreatePossession(
            user: $user,
            vault: $vault,
            possessionType: $car,
            name: '<b></b>',
        )->execute();
    }

    #[Test]
    public function it_refuses_a_type_of_another_account(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $this->expectException(ModelNotFoundException::class);

        new CreatePossession(
            user: $user,
            vault: $vault,
            possessionType: PossessionType::factory()->create(),
            name: 'Chrysler Sebring',
        )->execute();
    }

    #[Test]
    public function it_refuses_a_vault_of_another_account(): void
    {
        $user = User::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        new CreatePossession(
            user: $user,
            vault: Vault::factory()->create(),
            possessionType: PossessionType::factory()->create(['account_id' => $user->account_id]),
            name: 'Chrysler Sebring',
        )->execute();
    }

    #[Test]
    public function it_refuses_a_location_of_another_account(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $this->expectException(ModelNotFoundException::class);

        new CreatePossession(
            user: $user,
            vault: $vault,
            possessionType: PossessionType::factory()->create(['account_id' => $user->account_id]),
            name: 'Chrysler Sebring',
            location: Location::factory()->create(),
        )->execute();
    }

    #[Test]
    public function it_records_what_it_would_cost_to_replace(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $possession = new CreatePossession(
            user: $user,
            vault: $vault,
            possessionType: PossessionType::factory()->create(['account_id' => $user->account_id]),
            name: 'Porcelain cat',
            declaredValue: '120.5',
            declaredValueCurrency: CurrencyEnum::UsDollar,
        )->execute();

        $this->assertDatabaseHas('possessions', [
            'id' => $possession->id,
            'declared_value' => '120.50',
            'declared_value_currency' => 'usd',
        ]);
        $this->assertSame(0, $possession->transactions()->count());
    }

    #[Test]
    public function it_keeps_no_currency_without_a_value(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $possession = new CreatePossession(
            user: $user,
            vault: Vault::factory()->create(['account_id' => $user->account_id]),
            possessionType: PossessionType::factory()->create(['account_id' => $user->account_id]),
            name: 'Chrysler Sebring',
            declaredValueCurrency: CurrencyEnum::UsDollar,
        )->execute();

        $this->assertDatabaseHas('possessions', [
            'id' => $possession->id,
            'declared_value' => null,
            'declared_value_currency' => null,
        ]);
    }

    #[Test]
    public function it_refuses_a_negative_value_and_a_value_without_currency(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $type = PossessionType::factory()->create(['account_id' => $user->account_id]);

        $cases = [
            'declared_value' => ['declaredValue' => '-1', 'declaredValueCurrency' => CurrencyEnum::Euro],
            'declared_value_currency' => ['declaredValue' => '10'],
        ];

        foreach ($cases as $key => $arguments) {
            try {
                new CreatePossession(...['user' => $user, 'vault' => $vault, 'possessionType' => $type, 'name' => 'Dundie award', ...$arguments])->execute();

                $this->fail("{$key} was accepted.");
            } catch (ValidationException $e) {
                $this->assertArrayHasKey($key, $e->errors());
            }
        }

        $this->assertDatabaseCount('possessions', 0);
    }

    #[Test]
    public function it_writes_the_values_of_the_fields_of_its_type(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $car = PossessionType::factory()->create(['account_id' => $user->account_id]);
        $mileage = PossessionField::factory()->create(['possession_type_id' => $car->id, 'name' => 'Mileage', 'kind' => PossessionFieldKindEnum::Number]);
        $plate = PossessionField::factory()->create(['possession_type_id' => $car->id, 'name' => 'Registration']);

        $possession = new CreatePossession(
            user: $user,
            vault: $vault,
            possessionType: $car,
            name: 'Chrysler Sebring',
            values: [$mileage->uuid => '42000', $plate->uuid => ''],
        )->execute();

        $this->assertDatabaseHas('possession_field_values', ['possession_id' => $possession->id, 'possession_field_id' => $mileage->id, 'value' => '42000']);
        $this->assertDatabaseMissing('possession_field_values', ['possession_field_id' => $plate->id]);
    }

    #[Test]
    public function it_saves_nothing_when_a_value_does_not_fit_or_belongs_to_another_type(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $car = PossessionType::factory()->create(['account_id' => $user->account_id]);
        $mileage = PossessionField::factory()->create(['possession_type_id' => $car->id, 'kind' => PossessionFieldKindEnum::Number]);
        $elsewhere = PossessionField::factory()->create(['account_id' => $user->account_id]);

        try {
            new CreatePossession(
                user: $user,
                vault: $vault,
                possessionType: $car,
                name: 'Chrysler Sebring',
                values: [$mileage->uuid => 'a lot', $elsewhere->uuid => 'Dunder'],
                tagNames: ['insured'],
            )->execute();

            $this->fail('The values were accepted.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey("values.{$mileage->uuid}", $e->errors());
            $this->assertArrayHasKey("values.{$elsewhere->uuid}", $e->errors());
        }

        $this->assertDatabaseCount('possessions', 0);
        $this->assertDatabaseCount('possession_tags', 0);
    }

    #[Test]
    public function it_reuses_the_tags_of_the_account_and_creates_the_others(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $insured = PossessionTag::factory()->create(['account_id' => $user->account_id, 'name' => 'insured']);

        $possession = new CreatePossession(
            user: $user,
            vault: $vault,
            possessionType: PossessionType::factory()->create(['account_id' => $user->account_id]),
            name: 'Chili pot',
            tagNames: ['Insured', 'office party', 'Office Party', ' '],
        )->execute();

        $this->assertEquals(['insured', 'office party'], $possession->tags->pluck('name')->all());
        $this->assertTrue($possession->tags->first()->is($insured));
        $this->assertDatabaseCount('possession_tags', 2);
    }

    #[Test]
    public function it_refuses_a_tag_longer_than_fifty_characters(): void
    {
        $user = User::factory()->create();

        $this->expectException(ValidationException::class);

        new CreatePossession(
            user: $user,
            vault: Vault::factory()->create(['account_id' => $user->account_id]),
            possessionType: PossessionType::factory()->create(['account_id' => $user->account_id]),
            name: 'Chili pot',
            tagNames: [str_repeat('a', 51)],
        )->execute();
    }
}
