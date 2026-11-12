<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Api\PossessionConditions;

use App\Enums\VaultColourEnum;
use App\Models\PossessionCondition;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PossessionConditionControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<string, mixed> */
    private array $jsonStructure = [
        'data' => [
            'type',
            'id',
            'attributes' => [
                'name',
                'colour',
                'icon',
                'position',
                'created_at',
                'updated_at',
            ],
            'links' => ['self', 'web'],
        ],
    ];

    #[Test]
    public function it_lists_the_possession_conditions_of_the_account_in_its_order(): void
    {
        $user = User::factory()->create();
        PossessionCondition::factory()->create(['account_id' => $user->account_id, 'name' => 'Broken', 'position' => 2]);
        PossessionCondition::factory()->create(['account_id' => $user->account_id, 'name' => 'New', 'position' => 1]);
        PossessionCondition::factory()->create(['name' => 'Water damaged']);

        Sanctum::actingAs($user);

        $response = $this->json('GET', '/api/v1/possession-conditions');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [$this->jsonStructure['data']],
            'meta' => ['page', 'per_page', 'total'],
        ]);
        $response->assertJsonCount(2, 'data');
        $response->assertJsonPath('data.0.attributes.name', 'New');
        $response->assertJsonPath('data.1.attributes.position', 2);
        $response->assertJsonPath('meta.total', 2);
    }

    #[Test]
    public function it_shows_a_possession_condition(): void
    {
        $user = User::factory()->create();
        $possessionCondition = PossessionCondition::factory()->create(['account_id' => $user->account_id, 'name' => 'Mint', 'icon' => 'star']);

        Sanctum::actingAs($user);

        $this->json('GET', "/api/v1/possession-conditions/{$possessionCondition->uuid}")
            ->assertOk()
            ->assertJsonStructure($this->jsonStructure)
            ->assertJsonPath('data.type', 'possession_condition')
            ->assertJsonPath('data.attributes.name', 'Mint')
            ->assertJsonPath('data.attributes.icon', 'star');
    }

    #[Test]
    public function it_answers_not_found_for_a_condition_of_another_account(): void
    {
        $possessionCondition = PossessionCondition::factory()->create();

        Sanctum::actingAs(User::factory()->create());

        $this->json('GET', "/api/v1/possession-conditions/{$possessionCondition->uuid}")->assertNotFound();
        $this->json('PUT', "/api/v1/possession-conditions/{$possessionCondition->uuid}", ['name' => 'Mint', 'colour' => VaultColourEnum::Blue->value])->assertNotFound();
        $this->json('PUT', "/api/v1/possession-conditions/{$possessionCondition->uuid}/position", ['position' => 1])->assertNotFound();
        $this->json('DELETE', "/api/v1/possession-conditions/{$possessionCondition->uuid}")->assertNotFound();
    }

    #[Test]
    public function it_adds_a_possession_condition_in_blue_when_no_colour_is_given(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $this->json('POST', '/api/v1/possession-conditions', ['name' => 'Mint', 'icon' => 'star'])
            ->assertCreated()
            ->assertJsonStructure($this->jsonStructure)
            ->assertJsonPath('data.attributes.colour', VaultColourEnum::Blue->value)
            ->assertJsonPath('data.attributes.icon', 'star')
            ->assertJsonPath('data.attributes.position', 1);
    }

    #[Test]
    public function it_refuses_a_duplicate_name_an_unknown_colour_and_an_unknown_icon(): void
    {
        $user = User::factory()->create();
        PossessionCondition::factory()->create(['account_id' => $user->account_id, 'name' => 'Mint']);

        Sanctum::actingAs($user);

        $this->json('POST', '/api/v1/possession-conditions', ['name' => 'mint'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);

        $this->json('POST', '/api/v1/possession-conditions', ['name' => 'Like new', 'colour' => '#000000', 'icon' => 'dundie'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['colour', 'icon']);
    }

    #[Test]
    public function it_changes_a_possession_condition_and_removes_its_icon_when_left_out(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $possessionCondition = PossessionCondition::factory()->create(['account_id' => $user->account_id, 'name' => 'Worn', 'icon' => 'star']);

        Sanctum::actingAs($user);

        $this->json('PUT', "/api/v1/possession-conditions/{$possessionCondition->uuid}", ['name' => 'Well loved', 'colour' => VaultColourEnum::Amber->value])
            ->assertOk()
            ->assertJsonPath('data.attributes.name', 'Well loved')
            ->assertJsonPath('data.attributes.icon', null);
    }

    #[Test]
    public function it_deletes_a_possession_condition(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $possessionCondition = PossessionCondition::factory()->create(['account_id' => $user->account_id]);

        Sanctum::actingAs($user);

        $this->json('DELETE', "/api/v1/possession-conditions/{$possessionCondition->uuid}")->assertNoContent();

        $this->assertModelMissing($possessionCondition);
    }
}
