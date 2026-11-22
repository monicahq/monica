<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Vaults;

use App\Enums\RelationTargetEnum;
use App\Models\Contact;
use App\Models\Entity;
use App\Models\EntityType;
use App\Models\Field;
use App\Models\User;
use App\Models\Vault;
use App\ViewModels\Vaults\FieldChoicesViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FieldChoicesViewModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_offers_the_people_of_the_vault_whose_name_matches(): void
    {
        [$user, $scranton] = $this->vaultOf();
        $stamford = Vault::factory()->create(['account_id' => $user->account_id]);
        $field = Field::factory()->relation()->create(['account_id' => $user->account_id]);
        $dwight = Contact::factory()->create(['vault_id' => $scranton->id, 'first_name' => 'Dwight', 'last_name' => 'Schrute']);
        Contact::factory()->create(['vault_id' => $scranton->id, 'first_name' => 'Jim', 'last_name' => 'Halpert']);
        Contact::factory()->create(['vault_id' => $stamford->id, 'first_name' => 'Dwight', 'last_name' => 'Elsewhere']);

        $candidates = new FieldChoicesViewModel(user: $user, vault: $scranton, field: $field, query: 'dwight')->candidates();

        $this->assertSame([['uuid' => $dwight->uuid, 'name' => $dwight->name]], $candidates);
    }

    #[Test]
    public function it_offers_only_the_records_of_the_kind_the_field_points_at(): void
    {
        [$user, $scranton] = $this->vaultOf();
        $animals = EntityType::factory()->create(['account_id' => $user->account_id]);
        $plants = EntityType::factory()->create(['account_id' => $user->account_id]);
        $field = Field::factory()->relation()->create([
            'account_id' => $user->account_id,
            'relation_target' => RelationTargetEnum::Entity,
            'relation_entity_type_id' => $animals->id,
        ]);
        $sprinkles = Entity::factory()->create(['vault_id' => $scranton->id, 'entity_type_id' => $animals->id, 'name' => 'Sprinkles']);
        Entity::factory()->create(['vault_id' => $scranton->id, 'entity_type_id' => $plants->id, 'name' => 'Sprinkles the fern']);

        $candidates = new FieldChoicesViewModel(user: $user, vault: $scranton, field: $field, query: '')->candidates();

        $this->assertSame([['uuid' => $sprinkles->uuid, 'name' => 'Sprinkles']], $candidates);
    }

    #[Test]
    public function it_offers_at_most_twenty_five_at_a_time(): void
    {
        [$user, $scranton] = $this->vaultOf();
        $field = Field::factory()->relation()->create(['account_id' => $user->account_id]);
        Contact::factory()->count(26)->create(['vault_id' => $scranton->id]);

        $candidates = new FieldChoicesViewModel(user: $user, vault: $scranton, field: $field, query: '')->candidates();

        $this->assertCount(25, $candidates);
    }

    #[Test]
    public function it_offers_the_people_in_the_order_of_the_language_of_the_account(): void
    {
        $user = User::factory()->create(['locale' => 'fr_FR']);
        $scranton = Vault::factory()->create(['account_id' => $user->account_id]);
        $field = Field::factory()->relation()->create(['account_id' => $user->account_id]);
        Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $scranton->id, 'first_name' => 'Dwight', 'middle_name' => null, 'last_name' => 'Schrute']);
        Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $scranton->id, 'first_name' => 'Erin', 'middle_name' => null, 'last_name' => 'Élan']);

        $candidates = new FieldChoicesViewModel(user: $user, vault: $scranton, field: $field, query: '')->candidates();

        $this->assertSame(['Erin Élan', 'Dwight Schrute'], array_column($candidates, 'name'));
    }

    /** @return array{0: User, 1: Vault} */
    private function vaultOf(): array
    {
        $user = User::factory()->create();

        return [$user, Vault::factory()->create(['account_id' => $user->account_id])];
    }
}
