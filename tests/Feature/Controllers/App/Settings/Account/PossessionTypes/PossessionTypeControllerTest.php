<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Settings\Account\PossessionTypes;

use App\Enums\VaultColourEnum;
use App\Models\PossessionField;
use App\Models\PossessionFieldGroup;
use App\Models\PossessionType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PossessionTypeControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_shows_the_possession_types_of_the_account(): void
    {
        $user = User::factory()->create();
        PossessionType::factory()->create(['account_id' => $user->account_id, 'name' => 'Office equipment']);

        $this->actingAs($user)->get(route('settings.possessionTypes.index'))
            ->assertStatus(200)
            ->assertSee('Office equipment');
    }

    #[Test]
    public function it_creates_an_untitled_type_and_opens_it(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('settings.possessionTypes.create'));

        $possessionType = PossessionType::query()->where('account_id', $user->account_id)->sole();

        $response->assertRedirect(route('settings.possessionTypes.edit', $possessionType));
    }

    #[Test]
    public function it_shows_the_groups_and_fields_of_a_type(): void
    {
        $user = User::factory()->create();
        $possessionType = PossessionType::factory()->create(['account_id' => $user->account_id, 'name' => 'Office equipment']);
        $group = PossessionFieldGroup::factory()->create(['possession_type_id' => $possessionType->id, 'name' => 'Purchase']);
        PossessionField::factory()->inGroup($group)->create(['name' => 'Price']);
        PossessionField::factory()->create(['possession_type_id' => $possessionType->id, 'name' => 'Serial number']);

        $this->actingAs($user)->get(route('settings.possessionTypes.edit', $possessionType))
            ->assertStatus(200)
            ->assertSee('Office equipment')
            ->assertSee('Purchase')
            ->assertSee('Price')
            ->assertSee('Serial number')
            ->assertSee('aria-label="Teal"', false);
    }

    #[Test]
    public function it_saves_the_name_and_colour(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $possessionType = PossessionType::factory()->create(['account_id' => $user->account_id]);

        $this->actingAs($user)->put(route('settings.possessionTypes.update', $possessionType), [
            'name' => 'Office equipment',
            'colour' => VaultColourEnum::Teal->value,
            'icon' => 'briefcase',
        ])
            ->assertRedirect(route('settings.possessionTypes.edit', $possessionType))
            ->assertSessionHas('saved');

        $possessionType->refresh();
        $this->assertSame('Office equipment', $possessionType->name);
        $this->assertSame(VaultColourEnum::Teal->value, $possessionType->colour);
        $this->assertSame('briefcase', $possessionType->icon);
    }

    #[Test]
    public function it_removes_the_icon(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $possessionType = PossessionType::factory()->create(['account_id' => $user->account_id, 'icon' => 'car']);

        $this->actingAs($user)->put(route('settings.possessionTypes.update', $possessionType), [
            'name' => 'Company cars',
            'colour' => VaultColourEnum::Blue->value,
            'icon' => '',
        ])->assertRedirect(route('settings.possessionTypes.edit', $possessionType));

        $this->assertNull($possessionType->refresh()->icon);
    }

    #[Test]
    public function it_refuses_an_empty_name_or_a_colour_outside_the_palette(): void
    {
        $user = User::factory()->create();
        $possessionType = PossessionType::factory()->create(['account_id' => $user->account_id]);

        $this->actingAs($user)->put(route('settings.possessionTypes.update', $possessionType), [
            'name' => '',
            'colour' => '#ff0000',
            'icon' => 'beet',
        ])->assertSessionHasErrors(['name', 'colour', 'icon']);
    }

    #[Test]
    public function it_deletes_a_type(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $possessionType = PossessionType::factory()->create(['account_id' => $user->account_id, 'name' => 'Vehicle']);

        $this->actingAs($user)->delete(route('settings.possessionTypes.destroy', $possessionType))
            ->assertRedirect(route('settings.possessionTypes.index'))
            ->assertSessionHas('status');

        $this->assertModelMissing($possessionType);
    }
}
