<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Api;

use App\Enums\FieldTypeEnum;
use App\Models\Contact;
use App\Models\Field;
use App\Models\FieldValue;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SearchControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<int|string, mixed> */
    private array $jsonStructure = [
        'data' => [
            '*' => [
                'group',
                'label',
                'total',
                'next_page',
                'results' => [
                    '*' => ['type', 'id', 'name', 'vault' => ['id', 'name'], 'archived', 'date', 'matched'],
                ],
            ],
        ],
        'meta' => ['query', 'scope'],
    ];

    #[Test]
    public function it_lists_what_the_search_found(): void
    {
        $this->useSearchEngine();
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $dwight = $this->dwightIn($vault);
        $this->waitForSearchEngine();
        Sanctum::actingAs($user);

        $this->json('GET', "/api/v1/vaults/{$vault->uuid}/search", ['query' => 'farms'])
            ->assertOk()
            ->assertJsonStructure($this->jsonStructure)
            ->assertJsonPath('data.0.group', 'people')
            ->assertJsonPath('data.0.results.0.id', $dwight->uuid)
            ->assertJsonPath('data.0.results.0.matched.label', 'Employer')
            ->assertJsonPath('meta.scope', 'vault');
    }

    #[Test]
    public function it_keeps_a_sensitive_value_out_of_reach_of_a_key_without_the_scope(): void
    {
        $this->useSearchEngine();
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $this->dwightIn($vault);
        $this->waitForSearchEngine();

        $this->withToken($user->createToken('Dwight’s script')->plainTextToken)
            ->json('GET', "/api/v1/vaults/{$vault->uuid}/search", ['query' => 'brine'])
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    #[Test]
    public function it_reaches_a_sensitive_value_with_a_key_holding_the_scope(): void
    {
        $this->useSearchEngine();
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $this->dwightIn($vault);
        $this->waitForSearchEngine();

        $this->withToken($user->createToken('Dwight’s trusted script', ['sensitive-fields'])->plainTextToken)
            ->json('GET', "/api/v1/vaults/{$vault->uuid}/search", ['query' => 'brine'])
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    #[Test]
    public function it_returns_not_found_for_a_vault_of_another_account(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $otherVault = Vault::factory()->create();

        $this->json('GET', "/api/v1/vaults/{$otherVault->uuid}/search", ['query' => 'schrute'])
            ->assertNotFound();
    }

    #[Test]
    public function it_asks_for_a_query_of_at_least_two_characters(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        Sanctum::actingAs($user);

        $this->json('GET', "/api/v1/vaults/{$vault->uuid}/search", ['query' => 'd'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('query');
    }

    #[Test]
    public function it_says_search_is_unavailable_without_an_engine(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        Sanctum::actingAs($user);

        $this->json('GET', "/api/v1/vaults/{$vault->uuid}/search", ['query' => 'schrute'])
            ->assertStatus(503);
    }

    private function dwightIn(Vault $vault): Contact
    {
        $dwight = Contact::factory()->create(['account_id' => $vault->account_id, 'vault_id' => $vault->id, 'first_name' => 'Dwight', 'last_name' => 'Schrute']);

        foreach (['Employer' => ['Schrute Farms', false], 'Beet recipe' => ['brine', true]] as $label => [$text, $sensitive]) {
            $field = Field::factory()->create(['account_id' => $vault->account_id, 'type' => FieldTypeEnum::Text, 'label' => $label, 'is_sensitive' => $sensitive]);
            FieldValue::factory()->create(['valuable_type' => Contact::class, 'valuable_id' => $dwight->id, 'field_id' => $field->id, 'value_string' => $text]);
        }

        return $dwight;
    }
}
