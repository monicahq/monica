<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Settings\Account\PossessionTypes;

use App\Models\PossessionField;
use App\Models\PossessionFieldGroup;
use App\Models\PossessionType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PossessionFieldGroupControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_adds_an_untitled_group(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $possessionType = PossessionType::factory()->create(['account_id' => $user->account_id]);

        $this->actingAs($user)->post(route('settings.possessionFieldGroups.create', $possessionType))
            ->assertRedirect(route('settings.possessionTypes.edit', $possessionType))
            ->assertSessionHas('saved');

        $this->assertDatabaseHas('possession_field_groups', [
            'possession_type_id' => $possessionType->id,
        ]);
    }

    #[Test]
    public function it_renames_a_group(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $possessionType = PossessionType::factory()->create(['account_id' => $user->account_id]);
        $group = PossessionFieldGroup::factory()->create(['possession_type_id' => $possessionType->id]);

        $this->actingAs($user)->put(route('settings.possessionFieldGroups.update', [$possessionType, $group]), ['name' => 'Purchase'])
            ->assertRedirect(route('settings.possessionTypes.edit', $possessionType))
            ->assertSessionHas('saved');

        $this->assertSame('Purchase', $group->refresh()->name);
    }

    #[Test]
    public function it_refuses_an_empty_name(): void
    {
        $user = User::factory()->create();
        $possessionType = PossessionType::factory()->create(['account_id' => $user->account_id]);
        $group = PossessionFieldGroup::factory()->create(['possession_type_id' => $possessionType->id]);

        $this->actingAs($user)->put(route('settings.possessionFieldGroups.update', [$possessionType, $group]), ['name' => ''])
            ->assertSessionHasErrors('name', errorBag: "group{$group->uuid}");
    }

    #[Test]
    public function it_removes_a_group_and_keeps_its_fields(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $possessionType = PossessionType::factory()->create(['account_id' => $user->account_id]);
        $group = PossessionFieldGroup::factory()->create(['possession_type_id' => $possessionType->id]);
        $field = PossessionField::factory()->inGroup($group)->create();

        $this->actingAs($user)->delete(route('settings.possessionFieldGroups.destroy', [$possessionType, $group]))
            ->assertRedirect(route('settings.possessionTypes.edit', $possessionType))
            ->assertSessionHas('saved')
            ->assertSessionHas('focus', 'add-group');

        $this->assertModelMissing($group);
        $this->assertNull($field->refresh()->possession_field_group_id);
    }

    #[Test]
    public function it_returns_not_found_for_a_group_of_another_type_of_the_same_account(): void
    {
        $user = User::factory()->create();
        $possessionType = PossessionType::factory()->create(['account_id' => $user->account_id]);
        $otherType = PossessionType::factory()->create(['account_id' => $user->account_id]);
        $group = PossessionFieldGroup::factory()->create(['possession_type_id' => $otherType->id]);

        $this->actingAs($user)->put(route('settings.possessionFieldGroups.update', [$possessionType, $group]), ['name' => 'Purchase'])
            ->assertNotFound();

        $this->actingAs($user)->delete(route('settings.possessionFieldGroups.destroy', [$possessionType, $group]))
            ->assertNotFound();

        $this->assertModelExists($group);
    }
}
