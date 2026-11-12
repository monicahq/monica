<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Settings\Account\PossessionConditions;

use App\Enums\VaultColourEnum;
use App\Models\PossessionCondition;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PossessionConditionControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_shows_the_possession_conditions_of_the_account(): void
    {
        $user = User::factory()->create();
        PossessionCondition::factory()->create(['account_id' => $user->account_id, 'name' => 'Mint', 'position' => 1]);

        $this->actingAs($user)->get(route('settings.possessionConditions.index'))
            ->assertStatus(200)
            ->assertSee('Mint')
            ->assertSee('aria-label="Edit Mint"', false)
            ->assertSee('aria-label="Delete Mint"', false)
            ->assertSee('name="icon"', false)
            ->assertSee('name="colour"', false)
            ->assertSee('x-data="sortableRows"', false);
    }

    #[Test]
    public function it_adds_a_condition(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('settings.possessionConditions.create'), ['name' => 'Mint', 'colour' => VaultColourEnum::Teal->value, 'icon' => 'star'])
            ->assertRedirect(route('settings.possessionConditions.index'))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('possession_conditions', [
            'account_id' => $user->account_id,
            'name' => 'Mint',
            'colour' => VaultColourEnum::Teal->value,
            'icon' => 'star',
        ]);
    }

    #[Test]
    public function it_refuses_a_long_name_a_colour_outside_the_palette_and_an_unknown_icon(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('settings.possessionConditions.create'), ['name' => str_repeat('a', 51), 'colour' => '#000000', 'icon' => 'dundie'])
            ->assertSessionHasErrorsIn('newPossessionCondition', ['name', 'colour', 'icon']);
    }

    #[Test]
    public function it_returns_the_duplicate_refusal_to_the_add_form(): void
    {
        $user = User::factory()->create();
        PossessionCondition::factory()->create(['account_id' => $user->account_id, 'name' => 'Mint']);

        $this->actingAs($user)
            ->post(route('settings.possessionConditions.create'), ['name' => 'mint', 'colour' => VaultColourEnum::Blue->value])
            ->assertSessionHasErrorsIn('newPossessionCondition', ['name']);
    }

    #[Test]
    public function it_changes_a_condition(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $possessionCondition = PossessionCondition::factory()->create(['account_id' => $user->account_id, 'name' => 'Worn', 'icon' => 'star']);

        $this->actingAs($user)
            ->put(route('settings.possessionConditions.update', $possessionCondition), ['name' => 'Well loved', 'colour' => VaultColourEnum::Amber->value, 'icon' => ''])
            ->assertRedirect(route('settings.possessionConditions.index'))
            ->assertSessionHas('status');

        $possessionCondition->refresh();
        $this->assertEquals('Well loved', $possessionCondition->name);
        $this->assertEquals(VaultColourEnum::Amber->value, $possessionCondition->colour);
        $this->assertNull($possessionCondition->icon);
    }

    #[Test]
    public function it_refuses_a_change_that_breaks_the_rules_in_the_bag_of_the_condition(): void
    {
        $user = User::factory()->create();
        $possessionCondition = PossessionCondition::factory()->create(['account_id' => $user->account_id]);

        $this->actingAs($user)
            ->put(route('settings.possessionConditions.update', $possessionCondition), ['name' => '', 'colour' => 'red', 'icon' => 'dundie'])
            ->assertSessionHasErrorsIn("possessionCondition{$possessionCondition->uuid}", ['name', 'colour', 'icon']);
    }

    #[Test]
    public function it_deletes_a_condition(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $possessionCondition = PossessionCondition::factory()->create(['account_id' => $user->account_id, 'name' => 'Damaged']);

        $this->actingAs($user)
            ->delete(route('settings.possessionConditions.destroy', $possessionCondition))
            ->assertRedirect(route('settings.possessionConditions.index'))
            ->assertSessionHas('status');

        $this->assertModelMissing($possessionCondition);
    }
}
