<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\UpdatePossessionFieldValues;
use App\Enums\PossessionFieldKindEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Possession;
use App\Models\PossessionField;
use App\Models\PossessionFieldValue;
use App\Models\PossessionType;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpdatePossessionFieldValuesTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_stores_a_value_of_each_kind_normalised(): void
    {
        Queue::fake();

        [$user, $sebring, $fields] = $this->sebring();

        new UpdatePossessionFieldValues(
            user: $user,
            possession: $sebring,
            values: [
                $fields['plate']->uuid => '  PA-1234  ',
                $fields['mileage']->uuid => '120000.50',
                $fields['registration']->uuid => '2003-05-01',
                $fields['convertible']->uuid => true,
                $fields['fuel']->uuid => 'Petrol',
                $fields['rating']->uuid => '4',
            ],
        )->execute();

        $this->assertSame(
            ['PA-1234', '120000.5', '2003-05-01', '1', 'Petrol', '4'],
            array_map(
                fn (PossessionField $field): ?string => PossessionFieldValue::query()
                    ->where('possession_id', $sebring->id)
                    ->where('possession_field_id', $field->id)
                    ->value('value'),
                array_values($fields),
            ),
        );

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::PossessionUpdate
                && $job->parameters === ['name' => 'Chrysler Sebring'],
        );
    }

    #[Test]
    public function it_names_every_value_that_does_not_fit_and_writes_none(): void
    {
        [$user, $sebring, $fields] = $this->sebring();

        try {
            new UpdatePossessionFieldValues(
                user: $user,
                possession: $sebring,
                values: [
                    $fields['plate']->uuid => 'PA-1234',
                    $fields['mileage']->uuid => 'banana',
                    $fields['registration']->uuid => '2003-02-30',
                    $fields['convertible']->uuid => 'perhaps',
                    $fields['fuel']->uuid => 'Steam',
                    $fields['rating']->uuid => 6,
                ],
            )->execute();

            $this->fail('The values were accepted.');
        } catch (ValidationException $e) {
            $this->assertSame(
                ["values.{$fields['mileage']->uuid}", "values.{$fields['registration']->uuid}", "values.{$fields['convertible']->uuid}", "values.{$fields['fuel']->uuid}", "values.{$fields['rating']->uuid}"],
                array_keys($e->errors()),
            );
        }

        $this->assertDatabaseMissing('possession_field_values', ['possession_id' => $sebring->id]);
    }

    #[Test]
    public function it_refuses_a_field_of_another_type(): void
    {
        [$user, $sebring] = $this->sebring();
        $stranger = PossessionField::factory()->create();

        $this->expectException(ValidationException::class);

        new UpdatePossessionFieldValues(user: $user, possession: $sebring, values: [$stranger->uuid => 'Dunder Mifflin'])->execute();
    }

    #[Test]
    public function it_removes_a_value_given_nothing_and_leaves_the_others(): void
    {
        Queue::fake();

        [$user, $sebring, $fields] = $this->sebring();
        $plate = PossessionFieldValue::factory()->create(['possession_id' => $sebring->id, 'possession_field_id' => $fields['plate']->id]);
        $fuel = PossessionFieldValue::factory()->create(['possession_id' => $sebring->id, 'possession_field_id' => $fields['fuel']->id, 'value' => 'Petrol']);

        new UpdatePossessionFieldValues(user: $user, possession: $sebring, values: [$fields['plate']->uuid => null])->execute();

        $this->assertModelMissing($plate);
        $this->assertModelExists($fuel);
    }

    #[Test]
    public function it_removes_a_value_given_nothing_but_markup(): void
    {
        Queue::fake();

        [$user, $sebring, $fields] = $this->sebring();
        $plate = PossessionFieldValue::factory()->create(['possession_id' => $sebring->id, 'possession_field_id' => $fields['plate']->id]);

        new UpdatePossessionFieldValues(user: $user, possession: $sebring, values: [$fields['plate']->uuid => '<b> </b>'])->execute();

        $this->assertModelMissing($plate);
    }

    #[Test]
    public function it_refuses_a_number_too_large_to_be_finite(): void
    {
        [$user, $sebring, $fields] = $this->sebring();

        try {
            new UpdatePossessionFieldValues(user: $user, possession: $sebring, values: [$fields['mileage']->uuid => '1e999'])->execute();

            $this->fail('The value was accepted.');
        } catch (ValidationException $e) {
            $this->assertSame(["values.{$fields['mileage']->uuid}"], array_keys($e->errors()));
        }

        $this->assertDatabaseMissing('possession_field_values', ['possession_id' => $sebring->id]);
    }

    #[Test]
    public function it_refuses_a_possession_of_another_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        new UpdatePossessionFieldValues(
            user: User::factory()->create(),
            possession: Possession::factory()->create(),
            values: [],
        )->execute();
    }

    /** @return array{0: User, 1: Possession, 2: array<string, PossessionField>} */
    private function sebring(): array
    {
        $user = User::factory()->create();
        $car = PossessionType::factory()->create(['account_id' => $user->account_id, 'name' => 'Car']);
        $field = fn (string $name, PossessionFieldKindEnum $kind, ?array $options = null): PossessionField => PossessionField::factory()->create([
            'possession_type_id' => $car->id,
            'name' => $name,
            'kind' => $kind,
            'options' => $options,
        ]);

        $fields = [
            'plate' => $field('Plate', PossessionFieldKindEnum::Text),
            'mileage' => $field('Mileage', PossessionFieldKindEnum::Number),
            'registration' => $field('First registration', PossessionFieldKindEnum::Date),
            'convertible' => $field('Convertible', PossessionFieldKindEnum::Boolean),
            'fuel' => $field('Fuel', PossessionFieldKindEnum::Select, ['Petrol', 'Diesel']),
            'rating' => $field('How Michael rates it', PossessionFieldKindEnum::Rating),
        ];

        $sebring = Possession::factory()->create([
            'vault_id' => Vault::factory()->create(['account_id' => $user->account_id])->id,
            'possession_type_id' => $car->id,
            'name' => 'Chrysler Sebring',
        ]);

        return [$user, $sebring, $fields];
    }
}
