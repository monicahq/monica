<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Vaults;

use App\Enums\FieldTypeEnum;
use App\Models\Entity;
use App\Models\EntityType;
use App\Models\User;
use App\Models\Vault;
use App\ViewModels\Vaults\EntitiesViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EntitiesViewModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_lists_the_records_of_this_type_in_this_vault_by_name(): void
    {
        [$user, $vault, $type] = $this->vaultWithType();
        Entity::factory()->create(['vault_id' => $vault->id, 'entity_type_id' => $type->id, 'name' => 'Mose']);
        Entity::factory()->create(['vault_id' => $vault->id, 'entity_type_id' => $type->id, 'name' => 'Garbage']);

        $entities = new EntitiesViewModel(user: $user, vault: $vault, entityType: $type)->entities();

        $this->assertSame(['Garbage', 'Mose'], array_column($entities->items(), 'name'));
        $this->assertSame(route('entities.show', ['vault' => $vault, 'entityType' => $type, 'entity' => Entity::query()->where('name', 'Garbage')->firstOrFail()]), $entities->items()[0]['url']);
    }

    #[Test]
    public function it_leaves_out_the_records_of_another_vault_and_another_type(): void
    {
        [$user, $vault, $type] = $this->vaultWithType();
        $stamford = Vault::factory()->create(['account_id' => $user->account_id]);
        $other = EntityType::factory()->create(['account_id' => $user->account_id]);
        Entity::factory()->create(['vault_id' => $stamford->id, 'entity_type_id' => $type->id, 'name' => 'Bandit']);
        Entity::factory()->create(['vault_id' => $vault->id, 'entity_type_id' => $other->id, 'name' => 'Schrute Farms']);

        $this->assertCount(0, new EntitiesViewModel(user: $user, vault: $vault, entityType: $type)->entities());
    }

    #[Test]
    public function it_keeps_an_archived_record_in_its_place(): void
    {
        [$user, $vault, $type] = $this->vaultWithType();
        Entity::factory()->create(['vault_id' => $vault->id, 'entity_type_id' => $type->id, 'name' => 'Bandit']);
        Entity::factory()->archived()->create(['vault_id' => $vault->id, 'entity_type_id' => $type->id, 'name' => 'Mose']);
        Entity::factory()->create(['vault_id' => $vault->id, 'entity_type_id' => $type->id, 'name' => 'Sprinkles']);

        $entities = new EntitiesViewModel(user: $user, vault: $vault, entityType: $type)->entities()->items();

        $this->assertSame(['Bandit', 'Mose', 'Sprinkles'], array_column($entities, 'name'));
        $this->assertSame([false, true, false], array_column($entities, 'isArchived'));
    }

    #[Test]
    public function it_names_the_type_on_its_empty_list(): void
    {
        [$user, $vault, $type] = $this->vaultWithType();
        $type->update(['name_singular' => 'Animal']);

        $viewModel = new EntitiesViewModel(user: $user, vault: $vault, entityType: $type);

        $this->assertSame('Your Animals will line up here', $viewModel->emptyTitle());
        $this->assertSame('Add your first Animal', $viewModel->firstAddLabel());
    }

    #[Test]
    public function it_describes_the_empty_list_with_the_description_of_the_type(): void
    {
        [$user, $vault, $type] = $this->vaultWithType();
        $type->update(['description' => '  Dwight’s beets keep the farm dogs busy.  ']);

        $viewModel = new EntitiesViewModel(user: $user, vault: $vault, entityType: $type);

        $this->assertSame('Dwight’s beets keep the farm dogs busy.', $viewModel->emptyDescription());
    }

    #[Test]
    public function it_describes_the_empty_list_generically_when_the_type_has_no_description(): void
    {
        [$user, $vault, $type] = $this->vaultWithType();

        $type->update(['description' => null]);
        $withoutDescription = new EntitiesViewModel(user: $user, vault: $vault, entityType: $type)->emptyDescription();

        $type->update(['description' => '   ']);
        $withBlankDescription = new EntitiesViewModel(user: $user, vault: $vault, entityType: $type)->emptyDescription();

        $this->assertSame('The Animals you add to this vault will appear here, one row each.', $withoutDescription);
        $this->assertSame($withoutDescription, $withBlankDescription);
    }

    #[Test]
    public function it_marks_the_empty_list_with_the_default_icon_when_the_type_has_none(): void
    {
        [$user, $vault, $type] = $this->vaultWithType();
        $type->update(['icon' => null]);

        $this->assertSame(EntityType::DEFAULT_ICON, new EntitiesViewModel(user: $user, vault: $vault, entityType: $type)->icon());
    }

    #[Test]
    public function it_offers_the_way_to_add_one(): void
    {
        [$user, $vault, $type] = $this->vaultWithType();
        $type->update(['name_singular' => 'Animal']);
        $this->entityDefaultCarrying($vault, $type, ['Animal details' => ['species' => FieldTypeEnum::Text]]);

        $viewModel = new EntitiesViewModel(user: $user, vault: $vault, entityType: $type);

        $this->assertNull($viewModel->addRefusal());
        $this->assertSame(route('entities.new', ['vault' => $vault, 'entityType' => $type]), $viewModel->addUrl());
    }

    /** @return array{0: User, 1: Vault, 2: EntityType} */
    private function vaultWithType(): array
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $type = EntityType::factory()->create(['account_id' => $user->account_id, 'name_plural' => 'Animals']);

        return [$user, $vault, $type];
    }
}
