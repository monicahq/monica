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
    public function it_marks_an_archived_record(): void
    {
        [$user, $vault, $type] = $this->vaultWithType();
        Entity::factory()->archived()->create(['vault_id' => $vault->id, 'entity_type_id' => $type->id]);

        $this->assertTrue(new EntitiesViewModel(user: $user, vault: $vault, entityType: $type)->entities()->items()[0]['isArchived']);
    }

    #[Test]
    public function it_pages_the_records_twenty_at_a_time(): void
    {
        [$user, $vault, $type] = $this->vaultWithType();
        Entity::factory()->count(25)->create(['vault_id' => $vault->id, 'entity_type_id' => $type->id]);

        $firstPage = new EntitiesViewModel(user: $user, vault: $vault, entityType: $type)->entities();

        $this->assertCount(20, $firstPage);
        $this->assertSame(25, $firstPage->total());
        $this->assertSame(20, $firstPage->perPage());

        $this->app['request']->merge(['page' => 2]);

        $this->assertCount(5, new EntitiesViewModel(user: $user, vault: $vault, entityType: $type)->entities());
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
    public function it_holds_every_record_on_one_page_for_all(): void
    {
        [$user, $vault, $type] = $this->vaultWithType();
        Entity::factory()->count(25)->create(['vault_id' => $vault->id, 'entity_type_id' => $type->id]);

        $viewModel = new EntitiesViewModel(user: $user, vault: $vault, entityType: $type, size: 'all');

        $this->assertCount(25, $viewModel->entities());
        $this->assertFalse($viewModel->entities()->hasPages());
        $this->assertSame('all', $viewModel->size());
        $this->assertSame(20, $viewModel->defaultSize());
    }

    #[Test]
    public function it_names_the_type_in_its_empty_state(): void
    {
        [$user, $vault, $type] = $this->vaultWithType();

        $this->assertSame('No Animals in this vault yet', new EntitiesViewModel(user: $user, vault: $vault, entityType: $type)->emptyTitle());
    }

    /** @return array{0: User, 1: Vault, 2: EntityType} */
    #[Test]
    public function it_offers_the_way_to_add_one(): void
    {
        [$user, $vault, $type] = $this->vaultWithType();
        $type->update(['name_singular' => 'Animal']);
        $this->entityDefaultCarrying($vault, $type, ['Animal details' => ['species' => FieldTypeEnum::Text]]);

        $viewModel = new EntitiesViewModel(user: $user, vault: $vault, entityType: $type);

        $this->assertNull($viewModel->addRefusal());
        $this->assertSame('Add Animal', $viewModel->addLabel());
        $this->assertSame(route('entities.new', ['vault' => $vault, 'entityType' => $type]), $viewModel->addUrl());
    }

    #[Test]
    public function it_says_why_nothing_can_be_added(): void
    {
        [$user, $vault, $type] = $this->vaultWithType();

        $refusal = new EntitiesViewModel(user: $user, vault: $vault, entityType: $type)->addRefusal();

        $this->assertStringContainsString('Nothing can be added until this vault has a published template', (string) $refusal);
    }

    private function vaultWithType(): array
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $type = EntityType::factory()->create(['account_id' => $user->account_id, 'name_plural' => 'Animals']);

        return [$user, $vault, $type];
    }
}
