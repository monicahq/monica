<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Settings\Account\PossessionTypes;

use App\Enums\PossessionFieldKindEnum;
use App\Models\PossessionField;
use App\Models\PossessionFieldGroup;
use App\Models\PossessionType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PossessionFieldControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_adds_an_untitled_field(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $possessionType = PossessionType::factory()->create(['account_id' => $user->account_id]);

        $this->actingAs($user)->post(route('settings.possessionFields.create', $possessionType))
            ->assertRedirect(route('settings.possessionTypes.edit', $possessionType))
            ->assertSessionHas('saved');

        $this->assertDatabaseHas('possession_fields', [
            'possession_type_id' => $possessionType->id,
            'possession_field_group_id' => null,
        ]);
    }

    #[Test]
    public function it_adds_a_field_to_a_group_of_the_type(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $possessionType = PossessionType::factory()->create(['account_id' => $user->account_id]);
        $group = PossessionFieldGroup::factory()->create(['possession_type_id' => $possessionType->id]);

        $this->actingAs($user)->post(route('settings.possessionFields.create', $possessionType), ['group' => $group->uuid])
            ->assertRedirect(route('settings.possessionTypes.edit', $possessionType));

        $this->assertDatabaseHas('possession_fields', [
            'possession_type_id' => $possessionType->id,
            'possession_field_group_id' => $group->id,
        ]);
    }

    #[Test]
    public function it_refuses_a_group_of_another_type(): void
    {
        $user = User::factory()->create();
        $possessionType = PossessionType::factory()->create(['account_id' => $user->account_id]);
        $otherType = PossessionType::factory()->create(['account_id' => $user->account_id]);
        $group = PossessionFieldGroup::factory()->create(['possession_type_id' => $otherType->id]);

        $this->actingAs($user)->post(route('settings.possessionFields.create', $possessionType), ['group' => $group->uuid])
            ->assertSessionHasErrors('group');

        $this->assertDatabaseCount('possession_fields', 0);
    }

    #[Test]
    public function it_saves_the_name_kind_and_choices_of_a_field(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $possessionType = PossessionType::factory()->create(['account_id' => $user->account_id]);
        $field = PossessionField::factory()->create(['possession_type_id' => $possessionType->id]);

        $this->actingAs($user)->put(route('settings.possessionFields.update', [$possessionType, $field]), [
            'name' => 'Condition',
            'kind' => PossessionFieldKindEnum::Select->value,
            'options' => ['New', '', 'Used'],
        ])
            ->assertRedirect(route('settings.possessionTypes.edit', $possessionType))
            ->assertSessionHas('saved');

        $field->refresh();
        $this->assertSame('Condition', $field->name);
        $this->assertSame(PossessionFieldKindEnum::Select, $field->kind);
        $this->assertSame(['New', 'Used'], $field->options);
    }

    #[Test]
    public function it_refuses_an_empty_name_or_an_unknown_kind(): void
    {
        $user = User::factory()->create();
        $possessionType = PossessionType::factory()->create(['account_id' => $user->account_id]);
        $field = PossessionField::factory()->create(['possession_type_id' => $possessionType->id]);

        $this->actingAs($user)->put(route('settings.possessionFields.update', [$possessionType, $field]), [
            'name' => '',
            'kind' => 'wattage',
        ])->assertSessionHasErrors(['name', 'kind'], errorBag: "field{$field->uuid}");
    }

    #[Test]
    public function it_shows_a_refusal_of_the_action_as_an_error_on_the_form(): void
    {
        $user = User::factory()->create();
        $possessionType = PossessionType::factory()->create(['account_id' => $user->account_id]);
        $field = PossessionField::factory()->create(['possession_type_id' => $possessionType->id]);

        $this->actingAs($user)->put(route('settings.possessionFields.update', [$possessionType, $field]), [
            'name' => 'Condition',
            'kind' => PossessionFieldKindEnum::Select->value,
            'options' => ['New', 'new'],
        ])->assertSessionHasErrors('options', errorBag: "field{$field->uuid}");
    }

    #[Test]
    public function it_removes_a_field(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $possessionType = PossessionType::factory()->create(['account_id' => $user->account_id]);
        $field = PossessionField::factory()->create(['possession_type_id' => $possessionType->id]);

        $this->actingAs($user)->delete(route('settings.possessionFields.destroy', [$possessionType, $field]))
            ->assertRedirect(route('settings.possessionTypes.edit', $possessionType))
            ->assertSessionHas('saved')
            ->assertSessionHas('focus', 'add-field');

        $this->assertModelMissing($field);
    }

    #[Test]
    public function it_returns_not_found_for_a_field_of_another_type_of_the_same_account(): void
    {
        $user = User::factory()->create();
        $possessionType = PossessionType::factory()->create(['account_id' => $user->account_id]);
        $otherType = PossessionType::factory()->create(['account_id' => $user->account_id]);
        $field = PossessionField::factory()->create(['possession_type_id' => $otherType->id]);

        $this->actingAs($user)->put(route('settings.possessionFields.update', [$possessionType, $field]), [
            'name' => 'Condition',
            'kind' => PossessionFieldKindEnum::Text->value,
        ])->assertNotFound();

        $this->actingAs($user)->delete(route('settings.possessionFields.destroy', [$possessionType, $field]))
            ->assertNotFound();

        $this->assertModelExists($field);
    }
}
