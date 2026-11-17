<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Api\PossessionTypes;

use App\Enums\PossessionFieldKindEnum;
use App\Enums\VaultColourEnum;
use App\Models\PossessionField;
use App\Models\PossessionFieldGroup;
use App\Models\PossessionType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PossessionTypeControllerTest extends TestCase
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
                'field_count',
                'groups',
                'fields',
                'created_at',
                'updated_at',
            ],
            'links' => ['self', 'web'],
        ],
    ];

    #[Test]
    public function it_lists_the_possession_types_of_the_account_sorted_by_name(): void
    {
        $user = User::factory()->create();
        PossessionType::factory()->create(['account_id' => $user->account_id, 'name' => 'Vehicle']);
        PossessionType::factory()->create(['account_id' => $user->account_id, 'name' => 'Appliance']);
        PossessionType::factory()->create(['name' => 'Office equipment']);

        Sanctum::actingAs($user);

        $response = $this->json('GET', '/api/v1/possession-types');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [$this->jsonStructure['data']],
            'meta' => ['page', 'per_page', 'total'],
        ]);
        $response->assertJsonCount(2, 'data');
        $response->assertJsonPath('data.0.attributes.name', 'Appliance');
        $response->assertJsonPath('data.1.attributes.name', 'Vehicle');
        $response->assertJsonPath('meta.total', 2);
    }

    #[Test]
    public function it_pages_the_list(): void
    {
        $user = User::factory()->create();
        PossessionType::factory()->create(['account_id' => $user->account_id, 'name' => 'Appliance']);
        PossessionType::factory()->create(['account_id' => $user->account_id, 'name' => 'Vehicle']);

        Sanctum::actingAs($user);

        $response = $this->json('GET', '/api/v1/possession-types?per_page=1&page=2');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.attributes.name', 'Vehicle');
        $response->assertJsonPath('meta.page', 2);
        $response->assertJsonPath('meta.total', 2);
    }

    #[Test]
    public function it_shows_a_possession_type_with_its_groups_and_fields_in_order(): void
    {
        $user = User::factory()->create();
        $type = PossessionType::factory()->create(['account_id' => $user->account_id, 'name' => 'Office equipment']);
        $technical = PossessionFieldGroup::factory()->create(['possession_type_id' => $type->id, 'name' => 'Technical details', 'position' => 2]);
        $purchase = PossessionFieldGroup::factory()->create(['possession_type_id' => $type->id, 'name' => 'Purchase', 'position' => 1]);
        PossessionField::factory()->inGroup($purchase)->create(['name' => 'Shop', 'position' => 2]);
        PossessionField::factory()->inGroup($purchase)->create(['name' => 'Price', 'kind' => PossessionFieldKindEnum::Number, 'position' => 1]);
        PossessionField::factory()->inGroup($technical)->create(['name' => 'Power']);
        PossessionField::factory()->create(['possession_type_id' => $type->id, 'name' => 'Serial number']);

        Sanctum::actingAs($user);

        $response = $this->json('GET', "/api/v1/possession-types/{$type->uuid}");

        $response->assertOk();
        $response->assertJsonStructure($this->jsonStructure);
        $response->assertJsonPath('data.type', 'possession_type');
        $response->assertJsonPath('data.id', $type->uuid);
        $response->assertJsonPath('data.attributes.field_count', 4);
        $response->assertJsonPath('data.attributes.groups.0.name', 'Purchase');
        $response->assertJsonPath('data.attributes.groups.0.fields.0.name', 'Price');
        $response->assertJsonPath('data.attributes.groups.0.fields.0.kind', 'number');
        $response->assertJsonPath('data.attributes.groups.0.fields.0.group_id', $purchase->uuid);
        $response->assertJsonPath('data.attributes.groups.0.fields.1.name', 'Shop');
        $response->assertJsonPath('data.attributes.groups.1.name', 'Technical details');
        $response->assertJsonPath('data.attributes.groups.1.fields.0.name', 'Power');
        $response->assertJsonPath('data.attributes.fields.0.name', 'Serial number');
        $response->assertJsonPath('data.attributes.fields.0.group_id', null);
    }

    #[Test]
    public function it_returns_not_found_for_a_possession_type_of_another_account(): void
    {
        $type = PossessionType::factory()->create();

        Sanctum::actingAs(User::factory()->create());

        $this->json('GET', "/api/v1/possession-types/{$type->uuid}")->assertNotFound();
        $this->json('PUT', "/api/v1/possession-types/{$type->uuid}", ['name' => 'Vehicle', 'colour' => VaultColourEnum::Blue->value])->assertNotFound();
        $this->json('DELETE', "/api/v1/possession-types/{$type->uuid}")->assertNotFound();
        $this->assertModelExists($type);
    }

    #[Test]
    public function it_creates_a_possession_type(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->json('POST', '/api/v1/possession-types', [
            'name' => 'Office equipment',
            'colour' => VaultColourEnum::Green->value,
        ]);

        $response->assertCreated();
        $response->assertJsonStructure($this->jsonStructure);
        $response->assertJsonPath('data.attributes.name', 'Office equipment');
        $response->assertJsonPath('data.attributes.colour', VaultColourEnum::Green->value);
        $this->assertDatabaseHas('possession_types', [
            'account_id' => $user->account_id,
            'name' => 'Office equipment',
            'colour' => VaultColourEnum::Green->value,
        ]);
    }

    #[Test]
    public function it_creates_a_possession_type_without_a_colour(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->json('POST', '/api/v1/possession-types', ['name' => 'Vehicle']);

        $response->assertCreated();
        $response->assertJsonPath('data.attributes.name', 'Vehicle');
        $this->assertDatabaseHas('possession_types', [
            'account_id' => $user->account_id,
            'name' => 'Vehicle',
        ]);
    }

    #[Test]
    public function it_creates_a_possession_type_with_an_icon_and_refuses_one_outside_the_catalogue(): void
    {
        Queue::fake();

        Sanctum::actingAs(User::factory()->create());

        $this->json('POST', '/api/v1/possession-types', ['name' => 'Company cars', 'icon' => 'car'])
            ->assertCreated()
            ->assertJsonPath('data.attributes.icon', 'car');

        $this->json('POST', '/api/v1/possession-types', ['name' => 'Beets', 'icon' => 'beet'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['icon']);
    }

    #[Test]
    public function it_refuses_to_create_a_possession_type_without_a_name(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $response = $this->json('POST', '/api/v1/possession-types', ['colour' => VaultColourEnum::Green->value]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['name']);
        $this->assertDatabaseCount('possession_types', 0);
    }

    #[Test]
    public function it_keeps_what_is_left_out_of_an_update(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $type = PossessionType::factory()->create(['account_id' => $user->account_id, 'name' => 'Vehicle', 'colour' => VaultColourEnum::Teal->value, 'icon' => 'car']);

        Sanctum::actingAs($user);

        $response = $this->json('PUT', "/api/v1/possession-types/{$type->uuid}", ['name' => 'Company car']);

        $response->assertOk();
        $response->assertJsonStructure($this->jsonStructure);
        $response->assertJsonPath('data.attributes.name', 'Company car');
        $this->assertDatabaseHas('possession_types', [
            'id' => $type->id,
            'name' => 'Company car',
            'colour' => VaultColourEnum::Teal->value,
            'icon' => 'car',
        ]);
    }

    #[Test]
    public function it_clears_a_field_sent_as_null(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $type = PossessionType::factory()->create(['account_id' => $user->account_id, 'name' => 'Vehicle', 'icon' => 'car']);

        Sanctum::actingAs($user);

        $response = $this->json('PUT', "/api/v1/possession-types/{$type->uuid}", ['icon' => null]);

        $response->assertOk();
        $response->assertJsonPath('data.attributes.icon', null);
        $this->assertDatabaseHas('possession_types', [
            'id' => $type->id,
            'name' => 'Vehicle',
            'icon' => null,
        ]);
    }

    #[Test]
    public function it_refuses_a_name_or_a_colour_sent_as_null(): void
    {
        $user = User::factory()->create();
        $type = PossessionType::factory()->create(['account_id' => $user->account_id, 'name' => 'Vehicle']);

        Sanctum::actingAs($user);

        $response = $this->json('PUT', "/api/v1/possession-types/{$type->uuid}", ['name' => null, 'colour' => null]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['name', 'colour']);
        $this->assertSame('Vehicle', $type->refresh()->name);
    }

    #[Test]
    public function it_deletes_a_possession_type(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $type = PossessionType::factory()->create(['account_id' => $user->account_id]);

        Sanctum::actingAs($user);

        $this->json('DELETE', "/api/v1/possession-types/{$type->uuid}")->assertNoContent();

        $this->assertModelMissing($type);
    }

    #[Test]
    public function it_lists_the_possession_types_in_as_many_queries_for_many_as_for_one(): void
    {
        $user = User::factory()->create();
        $user->load('account');
        $this->possessionTypeWithFields($user, 'Appliance');

        Sanctum::actingAs($user);

        $forOne = $this->queriesOf(fn () => $this->json('GET', '/api/v1/possession-types'));

        $this->possessionTypeWithFields($user, 'Vehicle');
        $this->possessionTypeWithFields($user, 'Watch');

        $forThree = $this->queriesOf(fn () => $this->json('GET', '/api/v1/possession-types'));

        $this->assertSame($forOne, $forThree);
    }

    private function possessionTypeWithFields(User $user, string $name): void
    {
        $possessionType = PossessionType::factory()->create(['account_id' => $user->account_id, 'name' => $name]);
        $group = PossessionFieldGroup::factory()->create(['possession_type_id' => $possessionType->id]);
        PossessionField::factory()->create(['possession_type_id' => $possessionType->id, 'possession_field_group_id' => $group->id]);
        PossessionField::factory()->create(['possession_type_id' => $possessionType->id]);
    }

    private function queriesOf(callable $request): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        $request();

        return count(DB::getQueryLog());
    }
}
