<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Vaults;

use App\Enums\PossessionFieldKindEnum;
use App\Enums\PossessionStatusEnum;
use App\Models\Location;
use App\Models\Possession;
use App\Models\PossessionField;
use App\Models\PossessionFieldValue;
use App\Models\PossessionTag;
use App\Models\PossessionType;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PossessionControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_lists_the_possessions_once_the_vault_holds_some(): void
    {
        [$user, $vault] = $this->scranton();
        $sebring = Possession::factory()->featured(1)->create(['vault_id' => $vault->id, 'name' => 'Chrysler Sebring']);
        $dundie = Possession::factory()->create(['vault_id' => $vault->id, 'name' => 'Dundie award']);

        $this->actingAs($user)->get(route('possessions.index', $vault))
            ->assertStatus(200)
            ->assertSee('href="'.route('possessions.show', [$vault, $sebring]).'"', escape: false)
            ->assertSee('href="'.route('possessions.show', [$vault, $dundie]).'"', escape: false)
            ->assertSeeInOrder(['Chrysler Sebring', 'Dundie award'])
            ->assertSee('aria-label="Locations"', escape: false)
            ->assertSee('href="'.route('settings.locations.index').'"', escape: false)
            ->assertSee(route('possessions.new', $vault), escape: false);
    }

    #[Test]
    public function it_opens_the_form_with_the_type_chosen_from_the_empty_screen(): void
    {
        [$user, $vault] = $this->scranton();
        $vehicle = PossessionType::factory()->create(['account_id' => $user->account_id, 'name' => 'Vehicle']);
        PossessionField::factory()->create(['possession_type_id' => $vehicle->id, 'name' => 'Registration']);

        $this->actingAs($user)->get(route('possessions.new', [$vault, $vehicle]))
            ->assertStatus(200)
            ->assertSee('Registration');
    }

    #[Test]
    public function it_does_not_open_the_form_with_a_type_of_another_account(): void
    {
        [$user, $vault] = $this->scranton();
        $paper = PossessionType::factory()->create(['name' => 'Paper']);

        $this->actingAs($user)->get(route('possessions.new', [$vault, $paper]))
            ->assertNotFound();
    }

    #[Test]
    public function it_shows_the_form(): void
    {
        [$user, $vault] = $this->scranton();
        PossessionType::factory()->create(['account_id' => $user->account_id, 'name' => 'Vehicle']);

        $response = $this->actingAs($user)->get(route('possessions.new', $vault));

        $response->assertStatus(200);
        $response->assertSee('x-data="possessionTypePicker(', escape: false);
        $response->assertSee('aria-haspopup="listbox"', escape: false);
        $response->assertSee('role="listbox"', escape: false);
        $response->assertSee('role="combobox"', escape: false);
        $response->assertSee('id="possession-type-fields"', escape: false);
        $response->assertSee('x-data="tagInput(', escape: false);
        $response->assertSee('name="declared_value_currency"', escape: false);
        $response->assertSee('form="possession-form"', escape: false);
    }

    #[Test]
    public function it_shows_the_fields_of_the_type_chosen_before_a_refused_save(): void
    {
        [$user, $vault] = $this->scranton();
        $vehicle = PossessionType::factory()->create(['account_id' => $user->account_id, 'name' => 'Vehicle']);
        $registration = PossessionField::factory()->create(['possession_type_id' => $vehicle->id, 'name' => 'Registration']);

        $this->actingAs($user)
            ->withSession(['_old_input' => ['type' => $vehicle->uuid, 'values' => [$registration->uuid => 'PA-1234']]])
            ->get(route('possessions.new', $vault))
            ->assertStatus(200)
            ->assertSee('name="values['.$registration->uuid.']"', escape: false)
            ->assertSee('value="PA-1234"', escape: false);
    }

    #[Test]
    public function it_adds_a_possession(): void
    {
        Queue::fake();

        [$user, $vault] = $this->scranton();
        $vehicle = PossessionType::factory()->create(['account_id' => $user->account_id, 'name' => 'Vehicle']);
        $mileage = PossessionField::factory()->create(['possession_type_id' => $vehicle->id, 'kind' => PossessionFieldKindEnum::Number]);
        $parkingLot = Location::factory()->create(['account_id' => $user->account_id]);
        $response = $this->actingAs($user)->post(route('possessions.create', $vault), [
            'name' => 'Pontiac Trans Am',
            'type' => $vehicle->uuid,
            'values' => [$mileage->uuid => '42000'],
            'location' => $parkingLot->uuid,
            'declared_value' => '3500',
            'declared_value_currency' => 'usd',
            'tags' => ['insured', 'beet farm'],
            'is_featured' => '1',
        ]);

        $response->assertRedirect(route('possessions.index', $vault));
        $response->assertSessionHas('status');

        $possession = Possession::query()->where('name', 'Pontiac Trans Am')->firstOrFail();

        $this->assertSame($vault->id, $possession->vault_id);
        $this->assertSame($parkingLot->id, $possession->current_location_id);
        $this->assertSame('3500.00', $possession->declared_value);
        $this->assertTrue($possession->is_featured);
        $this->assertEquals(['beet farm', 'insured'], $possession->tags->pluck('name')->all());
        $this->assertSame('42000', $possession->values->first()->value);
    }

    #[Test]
    public function it_refuses_a_missing_name_and_type_and_keeps_what_was_typed(): void
    {
        [$user, $vault] = $this->scranton();

        $this->actingAs($user)
            ->from(route('possessions.new', $vault))
            ->post(route('possessions.create', $vault), ['name' => '', 'description' => 'Bought by Michael for Michael'])
            ->assertRedirect(route('possessions.new', $vault))
            ->assertSessionHasErrors(['name', 'type'])
            ->assertSessionHasInput('description', 'Bought by Michael for Michael');
    }

    #[Test]
    public function it_refuses_a_type_of_another_account_on_the_type(): void
    {
        [$user, $vault] = $this->scranton();

        $this->actingAs($user)
            ->from(route('possessions.new', $vault))
            ->post(route('possessions.create', $vault), ['name' => 'Stapler', 'type' => PossessionType::factory()->create()->uuid])
            ->assertRedirect(route('possessions.new', $vault))
            ->assertSessionHasErrors('type');

        $this->assertDatabaseCount('possessions', 0);
    }

    #[Test]
    public function a_value_that_does_not_fit_its_field_comes_back_on_that_field(): void
    {
        [$user, $vault] = $this->scranton();
        $vehicle = PossessionType::factory()->create(['account_id' => $user->account_id]);
        $mileage = PossessionField::factory()->create(['possession_type_id' => $vehicle->id, 'kind' => PossessionFieldKindEnum::Number]);

        $this->actingAs($user)
            ->from(route('possessions.new', $vault))
            ->post(route('possessions.create', $vault), ['name' => 'Pontiac Trans Am', 'type' => $vehicle->uuid, 'values' => [$mileage->uuid => 'a lot']])
            ->assertRedirect(route('possessions.new', $vault))
            ->assertSessionHasErrors("values.{$mileage->uuid}")
            ->assertSessionHasInput('name', 'Pontiac Trans Am');

        $this->assertDatabaseCount('possessions', 0);
    }

    #[Test]
    public function it_shows_a_possession(): void
    {
        [$user, $vault] = $this->scranton();
        $dundie = Possession::factory()->create(['vault_id' => $vault->id, 'name' => 'Dundie award']);

        $this->actingAs($user)->get(route('possessions.show', [$vault, $dundie]))
            ->assertStatus(200)
            ->assertSee('Dundie award');
    }

    #[Test]
    public function it_deletes_a_possession(): void
    {
        Queue::fake();

        [$user, $vault] = $this->scranton();
        $dundie = Possession::factory()->create(['vault_id' => $vault->id, 'name' => 'Dundie award']);

        $this->actingAs($user)->delete(route('possessions.destroy', [$vault, $dundie]))
            ->assertRedirect(route('possessions.index', $vault))
            ->assertSessionHas('status');

        $this->assertModelMissing($dundie);
    }

    #[Test]
    public function it_shows_the_form_filled_with_the_possession(): void
    {
        [$user, $vault] = $this->scranton();
        $dundie = Possession::factory()->create(['vault_id' => $vault->id, 'name' => 'Dundie award']);

        $this->actingAs($user)->get(route('possessions.edit', [$vault, $dundie]))
            ->assertStatus(200)
            ->assertSee('value="Dundie award"', escape: false)
            ->assertSee('action="'.route('possessions.update', [$vault, $dundie]).'"', escape: false)
            ->assertSee('Save changes');
    }

    #[Test]
    public function it_does_not_open_the_form_of_a_possession_through_another_vault(): void
    {
        [$user, $vault] = $this->scranton();
        $annex = Vault::factory()->create(['account_id' => $user->account_id]);
        $dundie = Possession::factory()->create(['vault_id' => $annex->id]);

        $this->actingAs($user)->get(route('possessions.edit', [$vault, $dundie]))->assertNotFound();
        $this->actingAs($user)->put(route('possessions.update', [$vault, $dundie]), ['name' => 'Dundie'])->assertNotFound();
    }

    #[Test]
    public function it_saves_a_possession_and_leaves_what_the_form_does_not_show(): void
    {
        Queue::fake();

        [$user, $vault] = $this->scranton();
        $warehouse = Location::factory()->create(['account_id' => $user->account_id, 'name' => 'Warehouse']);
        $sebring = Possession::factory()->create([
            'vault_id' => $vault->id,
            'name' => 'Sebring',
            'identifier' => '1C3EL55R',
            'status' => PossessionStatusEnum::Sold,
            'quantity' => 2,
            'is_featured' => true,
            'position' => 1,
        ]);
        $sebring->tags()->attach(PossessionTag::factory()->create(['account_id' => $user->account_id, 'name' => 'insured']));
        $mileage = PossessionField::factory()->create(['possession_type_id' => $sebring->possession_type_id, 'name' => 'Mileage', 'kind' => PossessionFieldKindEnum::Number]);
        $plate = PossessionField::factory()->create(['possession_type_id' => $sebring->possession_type_id, 'name' => 'Plate', 'kind' => PossessionFieldKindEnum::Text]);
        PossessionFieldValue::factory()->create(['possession_id' => $sebring->id, 'possession_field_id' => $plate->id, 'value' => 'PA-1234']);

        $this->actingAs($user)->put(route('possessions.update', [$vault, $sebring]), [
            'name' => 'Chrysler Sebring',
            'location' => $warehouse->uuid,
            'tags' => ['office party'],
            'values' => [$mileage->uuid => '121500', $plate->uuid => ''],
        ])
            ->assertRedirect(route('possessions.show', [$vault, $sebring]))
            ->assertSessionHas('status', 'Chrysler Sebring is saved.');

        $sebring->refresh();

        $this->assertSame('Chrysler Sebring', $sebring->name);
        $this->assertSame($warehouse->id, $sebring->current_location_id);
        $this->assertFalse($sebring->is_featured);
        $this->assertSame('1C3EL55R', $sebring->identifier);
        $this->assertSame(PossessionStatusEnum::Sold, $sebring->status);
        $this->assertSame(2, $sebring->quantity);
        $this->assertEquals(['office party'], $sebring->tags->pluck('name')->all());
        $this->assertSame(['Mileage' => '121500'], $sebring->values()->with('field')->get()->mapWithKeys(fn (PossessionFieldValue $value): array => [$value->field->name => $value->value])->all());
    }

    #[Test]
    public function it_refuses_an_empty_name_and_a_value_that_does_not_fit_and_saves_nothing(): void
    {
        [$user, $vault] = $this->scranton();
        $sebring = Possession::factory()->create(['vault_id' => $vault->id, 'name' => 'Sebring']);
        $mileage = PossessionField::factory()->create(['possession_type_id' => $sebring->possession_type_id, 'name' => 'Mileage', 'kind' => PossessionFieldKindEnum::Number]);

        $this->actingAs($user)->from(route('possessions.edit', [$vault, $sebring]))
            ->put(route('possessions.update', [$vault, $sebring]), ['name' => ''])
            ->assertRedirect(route('possessions.edit', [$vault, $sebring]))
            ->assertSessionHasErrors('name');

        $this->actingAs($user)->from(route('possessions.edit', [$vault, $sebring]))
            ->put(route('possessions.update', [$vault, $sebring]), ['name' => 'Chrysler Sebring', 'values' => [$mileage->uuid => 'banana']])
            ->assertRedirect(route('possessions.edit', [$vault, $sebring]))
            ->assertSessionHasErrors("values.{$mileage->uuid}");

        $this->assertSame('Sebring', $sebring->fresh()->name);
    }

    /** @return array{0: User, 1: Vault} */
    private function scranton(): array
    {
        $user = User::factory()->create();

        return [$user, Vault::factory()->create(['account_id' => $user->account_id])];
    }
}
