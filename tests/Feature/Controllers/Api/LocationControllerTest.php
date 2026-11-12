<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Api;

use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LocationControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<string, mixed> */
    private array $jsonStructure = [
        'data' => [
            'type',
            'id',
            'attributes' => [
                'parent_id',
                'name',
                'emoji',
                'address',
                'path',
                'effective_address',
                'location_count',
                'created_at',
                'updated_at',
            ],
            'links' => ['self', 'web'],
        ],
    ];

    #[Test]
    public function it_lists_the_locations_of_the_account_each_before_the_ones_inside_it(): void
    {
        $user = User::factory()->create();
        $scranton = Location::factory()->create(['account_id' => $user->account_id, 'name' => 'Scranton']);
        Location::factory()->inside($scranton)->create(['name' => 'Warehouse']);
        Location::factory()->create(['account_id' => $user->account_id, 'name' => 'Annex']);
        Location::factory()->create(['name' => 'Stamford']);

        Sanctum::actingAs($user);

        $response = $this->json('GET', '/api/v1/locations');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [$this->jsonStructure['data']],
            'meta' => ['page', 'per_page', 'total'],
        ]);
        $response->assertJsonCount(3, 'data');
        $response->assertJsonPath('data.0.attributes.name', 'Annex');
        $response->assertJsonPath('data.1.attributes.name', 'Scranton');
        $response->assertJsonPath('data.2.attributes.path', 'Scranton › Warehouse');
        $response->assertJsonPath('data.2.attributes.parent_id', $scranton->uuid);
        $response->assertJsonPath('meta.total', 3);
    }

    #[Test]
    public function it_pages_the_list_in_the_order_of_the_tree(): void
    {
        $user = User::factory()->create();
        $scranton = Location::factory()->create(['account_id' => $user->account_id, 'name' => 'Scranton']);
        Location::factory()->inside($scranton)->create(['name' => 'Warehouse']);
        Location::factory()->create(['account_id' => $user->account_id, 'name' => 'Stamford']);

        Sanctum::actingAs($user);

        $response = $this->json('GET', '/api/v1/locations?per_page=2&page=2');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.attributes.name', 'Stamford');
        $response->assertJsonPath('meta.page', 2);
        $response->assertJsonPath('meta.total', 3);
    }

    #[Test]
    public function it_answers_an_empty_list_for_an_account_with_no_location(): void
    {
        Location::factory()->create(['name' => 'Stamford']);

        Sanctum::actingAs(User::factory()->create());

        $response = $this->json('GET', '/api/v1/locations');

        $response->assertOk();
        $response->assertJsonCount(0, 'data');
    }

    #[Test]
    public function it_shows_a_location_with_its_path_and_the_address_it_falls_back_on(): void
    {
        $user = User::factory()->create();
        $scranton = Location::factory()->create(['account_id' => $user->account_id, 'name' => 'Dunder Mifflin Scranton', 'address' => '1725 Slough Avenue']);
        $warehouse = Location::factory()->inside($scranton)->create(['name' => 'Warehouse']);
        $shelf = Location::factory()->inside($warehouse)->create(['name' => 'Shelf 3', 'emoji' => '📦']);

        Sanctum::actingAs($user);

        $response = $this->json('GET', "/api/v1/locations/{$shelf->uuid}");

        $response->assertOk();
        $response->assertJsonStructure($this->jsonStructure);
        $response->assertJsonPath('data.type', 'location');
        $response->assertJsonPath('data.attributes.path', 'Dunder Mifflin Scranton › Warehouse › Shelf 3');
        $response->assertJsonPath('data.attributes.emoji', '📦');
        $response->assertJsonPath('data.attributes.address', null);
        $response->assertJsonPath('data.attributes.effective_address.address', '1725 Slough Avenue');
        $response->assertJsonPath('data.attributes.effective_address.location_id', $scranton->uuid);
        $response->assertJsonPath('data.attributes.location_count', 0);
    }

    #[Test]
    public function it_returns_not_found_for_a_location_of_another_account(): void
    {
        $stamford = Location::factory()->create();

        Sanctum::actingAs(User::factory()->create());

        $this->json('GET', "/api/v1/locations/{$stamford->uuid}")->assertNotFound();
        $this->json('PUT', "/api/v1/locations/{$stamford->uuid}", ['name' => 'Annex'])->assertNotFound();
        $this->json('DELETE', "/api/v1/locations/{$stamford->uuid}")->assertNotFound();
    }

    #[Test]
    public function it_creates_a_location(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $scranton = Location::factory()->create(['account_id' => $user->account_id, 'name' => 'Scranton']);

        Sanctum::actingAs($user);

        $response = $this->json('POST', '/api/v1/locations', [
            'name' => 'Warehouse',
            'parent_id' => $scranton->uuid,
            'emoji' => '📦',
            'address' => '1725 Slough Avenue',
        ]);

        $response->assertCreated();
        $response->assertJsonStructure($this->jsonStructure);
        $response->assertJsonPath('data.attributes.path', 'Scranton › Warehouse');
        $this->assertDatabaseHas('locations', [
            'account_id' => $user->account_id,
            'parent_id' => $scranton->id,
            'name' => 'Warehouse',
            'emoji' => '📦',
            'address' => '1725 Slough Avenue',
        ]);
    }

    #[Test]
    public function it_validates_what_a_location_is_created_with(): void
    {
        $stamford = Location::factory()->create();

        Sanctum::actingAs(User::factory()->create());

        $response = $this->json('POST', '/api/v1/locations', [
            'emoji' => 'ab',
            'parent_id' => $stamford->uuid,
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['name', 'emoji', 'parent_id']);
        $this->assertDatabaseCount('locations', 1);
    }

    #[Test]
    public function it_updates_a_location_and_clears_what_is_left_out(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $scranton = Location::factory()->create(['account_id' => $user->account_id]);
        $warehouse = Location::factory()->inside($scranton)->create(['name' => 'Warehouse', 'emoji' => '📦', 'address' => '1725 Slough Avenue']);

        Sanctum::actingAs($user);

        $response = $this->json('PUT', "/api/v1/locations/{$warehouse->uuid}", ['name' => 'Annex']);

        $response->assertOk();
        $response->assertJsonStructure($this->jsonStructure);
        $response->assertJsonPath('data.attributes.name', 'Annex');
        $response->assertJsonPath('data.attributes.parent_id', null);

        $warehouse->refresh();
        $this->assertNull($warehouse->parent_id);
        $this->assertNull($warehouse->emoji);
        $this->assertNull($warehouse->address);
    }

    #[Test]
    public function it_refuses_to_put_a_location_inside_one_it_holds(): void
    {
        $user = User::factory()->create();
        $scranton = Location::factory()->create(['account_id' => $user->account_id, 'name' => 'Scranton']);
        $warehouse = Location::factory()->inside($scranton)->create();

        Sanctum::actingAs($user);

        $response = $this->json('PUT', "/api/v1/locations/{$scranton->uuid}", [
            'name' => 'Scranton',
            'parent_id' => $warehouse->uuid,
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['parent_id']);
        $this->assertNull($scranton->refresh()->parent_id);
    }

    #[Test]
    public function it_deletes_a_location(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $annex = Location::factory()->create(['account_id' => $user->account_id]);

        Sanctum::actingAs($user);

        $this->json('DELETE', "/api/v1/locations/{$annex->uuid}")->assertNoContent();

        $this->assertModelMissing($annex);
    }

    #[Test]
    public function it_refuses_to_delete_a_location_holding_another(): void
    {
        $user = User::factory()->create();
        $scranton = Location::factory()->create(['account_id' => $user->account_id]);
        Location::factory()->inside($scranton)->create();

        Sanctum::actingAs($user);

        $response = $this->json('DELETE', "/api/v1/locations/{$scranton->uuid}");

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['location']);
        $this->assertModelExists($scranton);
    }

    #[Test]
    public function it_refuses_a_parent_that_is_not_a_uuid_before_looking_it_up(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->json('POST', '/api/v1/locations', [
            'name' => 'Annex',
            'parent_id' => 'dunder-mifflin',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['parent_id']);
    }
}
