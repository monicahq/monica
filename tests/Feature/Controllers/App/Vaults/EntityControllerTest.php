<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Vaults;

use App\Enums\FieldTypeEnum;
use App\Models\Entity;
use App\Models\EntityType;
use App\Models\Field;
use App\Models\StructureVersion;
use App\Models\User;
use App\Models\Vault;
use App\Models\VaultDefaultTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EntityControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_shows_the_records_of_a_type(): void
    {
        [$user, $vault, $type] = $this->vaultWithType();
        VaultDefaultTemplate::factory()->forEntity($type)->create(['vault_id' => $vault->id]);
        Entity::factory()->create(['vault_id' => $vault->id, 'entity_type_id' => $type->id, 'name' => 'Mose']);

        $this->actingAs($user)->get(route('entities.index', ['vault' => $vault, 'entityType' => $type]))
            ->assertStatus(200)
            ->assertSee('Animals')
            ->assertSee('Mose')
            ->assertDontSee('This type is turned off.');
    }

    #[Test]
    public function it_says_so_when_the_type_is_turned_off(): void
    {
        [$user, $vault, $type] = $this->vaultWithType(['is_active' => false]);
        Entity::factory()->create(['vault_id' => $vault->id, 'entity_type_id' => $type->id]);

        $this->actingAs($user)->get(route('entities.index', ['vault' => $vault, 'entityType' => $type]))
            ->assertStatus(200)
            ->assertSee('This type is turned off. You can view and edit the existing profiles, but you cannot add new ones.');
    }

    #[Test]
    public function it_says_when_the_vault_holds_none_of_them(): void
    {
        [$user, $vault, $type] = $this->vaultWithType();
        VaultDefaultTemplate::factory()->forEntity($type)->create(['vault_id' => $vault->id]);

        $this->actingAs($user)->get(route('entities.index', ['vault' => $vault, 'entityType' => $type]))
            ->assertStatus(200)
            ->assertSee('No Animals in this vault yet');
    }

    #[Test]
    public function it_answers_not_found_for_a_type_hidden_in_this_vault(): void
    {
        [$user, $vault, $type] = $this->vaultWithType();
        $stamford = Vault::factory()->create(['account_id' => $user->account_id]);
        VaultDefaultTemplate::factory()->forEntity($type)->create(['vault_id' => $stamford->id]);

        $this->actingAs($user)->get(route('entities.index', ['vault' => $vault, 'entityType' => $type]))
            ->assertNotFound();
    }

    #[Test]
    public function it_shows_the_form_of_a_type(): void
    {
        [$user, $vault, $type] = $this->vaultWithType();
        $this->entityDefaultCarrying($vault, $type, [
            'Animal details' => [
                'species' => FieldTypeEnum::Text,
                'sex' => FieldTypeEnum::Radio,
                'birthday' => FieldTypeEnum::RecurringDate,
                'adoption_date' => FieldTypeEnum::Date,
                'photo' => FieldTypeEnum::Image,
                'vaccinations' => FieldTypeEnum::Repeater,
                'home' => FieldTypeEnum::Address,
            ],
        ]);

        $this->actingAs($user)->get(route('entities.new', ['vault' => $vault, 'entityType' => $type]))
            ->assertStatus(200)
            ->assertSee('New Animal')
            ->assertSee('Animal details')
            ->assertSee('Species')
            ->assertSee('Adoption Date')
            ->assertSee('enctype="multipart/form-data"', false);
    }

    #[Test]
    public function it_answers_not_found_for_the_form_of_a_hidden_type(): void
    {
        [$user, $vault, $type] = $this->vaultWithType();

        $this->actingAs($user)->get(route('entities.new', ['vault' => $vault, 'entityType' => $type]))
            ->assertNotFound();
    }

    #[Test]
    public function it_answers_not_found_for_the_form_through_the_wrong_vault(): void
    {
        [$user, $vault, $type] = $this->vaultWithType();
        $this->entityDefaultCarrying($vault, $type, ['Animal details' => ['species' => FieldTypeEnum::Text]]);
        $stamford = Vault::factory()->create(['account_id' => $user->account_id]);

        $this->actingAs($user)->get(route('entities.new', ['vault' => $stamford, 'entityType' => $type]))
            ->assertNotFound();
    }

    #[Test]
    public function it_answers_not_found_for_the_form_of_a_turned_off_type(): void
    {
        [$user, $vault, $type] = $this->vaultWithType();
        $this->entityDefaultCarrying($vault, $type, ['Animal details' => ['species' => FieldTypeEnum::Text]]);
        Entity::factory()->create(['vault_id' => $vault->id, 'entity_type_id' => $type->id]);
        $type->update(['is_active' => false]);

        $this->actingAs($user)->get(route('entities.new', ['vault' => $vault, 'entityType' => $type]))
            ->assertNotFound();
    }

    #[Test]
    public function it_creates_an_entity_and_goes_back_to_the_list(): void
    {
        Queue::fake();

        [$user, $vault, $type] = $this->vaultWithType();
        $version = $this->entityDefaultCarrying($vault, $type, ['Animal details' => ['species' => FieldTypeEnum::Text]]);
        $species = $version->inputFields()->firstOrFail();

        $this->actingAs($user)->post(route('entities.create', ['vault' => $vault, 'entityType' => $type]), [
            'name' => 'Mose’s goat',
            'structure_version_id' => $version->id,
            'fields' => [$species->id => 'Goat'],
        ])
            ->assertRedirect(route('entities.index', ['vault' => $vault, 'entityType' => $type]))
            ->assertSessionHas('status', 'Mose’s goat is written down.');

        $this->assertDatabaseHas('entities', ['vault_id' => $vault->id, 'entity_type_id' => $type->id, 'name' => 'Mose’s goat']);
    }

    #[Test]
    public function it_refuses_an_entity_without_a_name(): void
    {
        [$user, $vault, $type] = $this->vaultWithType();
        $version = $this->entityDefaultCarrying($vault, $type, ['Animal details' => ['species' => FieldTypeEnum::Text]]);

        $this->actingAs($user)->post(route('entities.create', ['vault' => $vault, 'entityType' => $type]), [
            'name' => '',
            'structure_version_id' => $version->id,
        ])->assertSessionHasErrors('name');

        $this->assertSame(0, Entity::query()->count());
    }

    #[Test]
    public function it_refuses_a_required_field_left_empty(): void
    {
        [$user, $vault, $type] = $this->vaultWithType();
        $version = $this->entityDefaultCarrying($vault, $type, ['Animal details' => ['species' => FieldTypeEnum::Text]]);
        $species = $version->inputFields()->firstOrFail();
        Field::query()->whereKey($species->id)->update(['is_required' => true]);

        $this->actingAs($user)->post(route('entities.create', ['vault' => $vault, 'entityType' => $type]), [
            'name' => 'Mose’s goat',
            'structure_version_id' => $version->id,
        ])->assertSessionHasErrors("fields.{$species->id}");

        $this->assertSame(0, Entity::query()->count());
    }

    #[Test]
    public function it_refuses_a_form_drawn_from_another_version(): void
    {
        [$user, $vault, $type] = $this->vaultWithType();
        $this->entityDefaultCarrying($vault, $type, ['Animal details' => ['species' => FieldTypeEnum::Text]]);

        $this->actingAs($user)->post(route('entities.create', ['vault' => $vault, 'entityType' => $type]), [
            'name' => 'Mose’s goat',
            'structure_version_id' => StructureVersion::factory()->published()->create()->id,
        ])->assertSessionHasErrors(['name' => 'The template changed since the form was opened. Open the form again to see what it asks now.']);
    }

    #[Test]
    public function it_goes_back_to_the_list_when_the_type_was_turned_off_while_the_form_was_open(): void
    {
        [$user, $vault, $type] = $this->vaultWithType();
        $version = $this->entityDefaultCarrying($vault, $type, ['Animal details' => ['species' => FieldTypeEnum::Text]]);
        Entity::factory()->create(['vault_id' => $vault->id, 'entity_type_id' => $type->id]);
        $type->update(['is_active' => false]);

        $this->actingAs($user)->post(route('entities.create', ['vault' => $vault, 'entityType' => $type]), [
            'name' => 'Mose’s goat',
            'structure_version_id' => $version->id,
        ])->assertRedirect(route('entities.index', ['vault' => $vault, 'entityType' => $type]));

        $this->assertSame(1, Entity::query()->count());
    }

    #[Test]
    public function it_refuses_a_name_made_only_of_markup(): void
    {
        [$user, $vault, $type] = $this->vaultWithType();
        $version = $this->entityDefaultCarrying($vault, $type, ['Animal details' => ['species' => FieldTypeEnum::Text]]);

        $this->actingAs($user)->post(route('entities.create', ['vault' => $vault, 'entityType' => $type]), [
            'name' => '<b></b>',
            'structure_version_id' => $version->id,
        ])->assertSessionHasErrors(['name' => 'Write a name for this record.']);
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array{0: User, 1: Vault, 2: EntityType}
     */
    private function vaultWithType(array $attributes = []): array
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $type = EntityType::factory()->create(array_merge([
            'account_id' => $user->account_id,
            'name_singular' => 'Animal',
            'name_plural' => 'Animals',
        ], $attributes));

        return [$user, $vault, $type];
    }
}
