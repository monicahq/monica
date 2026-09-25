<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Vaults;

use App\Models\Entity;
use App\Models\EntityType;
use App\Models\User;
use App\Models\Vault;
use App\Models\VaultDefaultTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
            'name_plural' => 'Animals',
        ], $attributes));

        return [$user, $vault, $type];
    }
}
