<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Api;

use App\Enums\ValueListEnum;
use App\Models\User;
use App\Models\ValueList;
use App\Models\ValueListItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ValueListControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<int|string, mixed> */
    private array $jsonStructure = [
        'type',
        'id',
        'attributes' => ['name', 'items'],
    ];

    #[Test]
    public function it_lists_the_value_lists_of_the_account_with_their_choices(): void
    {
        $user = User::factory()->create();
        $departments = ValueList::factory()->create(['account_id' => $user->account_id, 'name' => 'Departments']);
        ValueListItem::factory()->create(['account_id' => $user->account_id, 'value_list_id' => $departments->id, 'label' => 'Sales', 'position' => 1]);
        ValueListItem::factory()->create(['account_id' => $user->account_id, 'value_list_id' => $departments->id, 'label' => 'Accounting', 'position' => 2]);

        Sanctum::actingAs($user);

        $response = $this->json('GET', '/api/v1/value-lists', ['per_page' => 100]);

        $response->assertOk();
        $response->assertJsonStructure(['data' => ['*' => $this->jsonStructure], 'meta' => ['page', 'per_page', 'total']]);
        $response->assertJsonFragment([
            'id' => $departments->id,
            'attributes' => [
                'name' => 'Departments',
                'items' => [
                    ['id' => $departments->items()->where('label', 'Sales')->value('id'), 'label' => 'Sales', 'country_code' => null, 'is_active' => true, 'position' => 1],
                    ['id' => $departments->items()->where('label', 'Accounting')->value('id'), 'label' => 'Accounting', 'country_code' => null, 'is_active' => true, 'position' => 2],
                ],
            ],
        ]);
    }

    #[Test]
    public function it_leaves_out_the_lists_of_another_account_and_the_lists_of_one_field(): void
    {
        $user = User::factory()->create();
        ValueList::factory()->create(['name' => 'Vance Refrigeration models']);
        ValueList::factory()->create(['account_id' => $user->account_id, 'name' => 'Dwight’s beet varieties', 'is_shared' => false]);

        Sanctum::actingAs($user);

        $this->json('GET', '/api/v1/value-lists', ['per_page' => 100])
            ->assertOk()
            ->assertDontSee('Vance Refrigeration models')
            ->assertDontSee('beet varieties');
    }

    #[Test]
    public function it_gives_each_country_its_code(): void
    {
        $user = User::factory()->create();
        $countries = ValueList::factory()->create(['account_id' => $user->account_id, 'key' => ValueListEnum::Countries->value]);
        $japan = ValueListItem::factory()->create(['account_id' => $user->account_id, 'value_list_id' => $countries->id, 'label' => 'Japan', 'country_code' => 'JP']);
        $france = ValueListItem::factory()->create(['account_id' => $user->account_id, 'value_list_id' => $countries->id, 'key' => 'fr', 'label' => 'France']);

        Sanctum::actingAs($user);

        $this->json('GET', '/api/v1/value-lists', ['per_page' => 100])
            ->assertOk()
            ->assertJsonFragment(['id' => $japan->id, 'label' => 'Japan', 'country_code' => 'JP'])
            ->assertJsonFragment(['id' => $france->id, 'label' => 'France', 'country_code' => 'FR']);
    }

    #[Test]
    public function it_creates_a_value_list(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $this->json('POST', '/api/v1/value-lists', ['name' => 'Paper suppliers'])
            ->assertCreated()
            ->assertJsonStructure(['data' => $this->jsonStructure])
            ->assertJsonPath('data.attributes.name', 'Paper suppliers');

        $this->assertDatabaseHas('value_lists', ['account_id' => $user->account_id, 'name' => 'Paper suppliers', 'is_shared' => true]);
    }

    #[Test]
    public function it_refuses_a_value_list_without_a_name(): void
    {
        Queue::fake();

        Sanctum::actingAs(User::factory()->create());

        $this->json('POST', '/api/v1/value-lists', ['name' => ''])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('name');
    }

    #[Test]
    public function it_renames_a_value_list(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $suppliers = ValueList::factory()->create(['account_id' => $user->account_id, 'name' => 'Suppliers']);

        Sanctum::actingAs($user);

        $this->json('PUT', "/api/v1/value-lists/{$suppliers->id}", ['name' => 'Paper suppliers'])
            ->assertOk()
            ->assertJsonPath('data.attributes.name', 'Paper suppliers');

        $this->assertSame('Paper suppliers', $suppliers->refresh()->name);
    }

    #[Test]
    public function it_does_not_find_a_list_that_belongs_to_one_field(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $beets = ValueList::factory()->create(['account_id' => $user->account_id, 'is_shared' => false]);

        Sanctum::actingAs($user);

        $this->json('PUT', "/api/v1/value-lists/{$beets->id}", ['name' => 'Beets'])->assertNotFound();
    }

    #[Test]
    public function it_deletes_a_value_list(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $suppliers = ValueList::factory()->create(['account_id' => $user->account_id]);

        Sanctum::actingAs($user);

        $this->json('DELETE', "/api/v1/value-lists/{$suppliers->id}")->assertNoContent();

        $this->assertSoftDeleted($suppliers);
    }

    #[Test]
    public function it_refuses_to_delete_a_list_the_application_reads(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $phoneLabels = ValueList::factory()->supplied()->create(['account_id' => $user->account_id]);

        Sanctum::actingAs($user);

        $this->json('DELETE', "/api/v1/value-lists/{$phoneLabels->id}")
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['valueList' => 'This list stays.']);
    }

    #[Test]
    public function it_does_not_find_a_list_of_another_account(): void
    {
        Queue::fake();

        $vanceRefrigeration = ValueList::factory()->create();

        Sanctum::actingAs(User::factory()->create());

        $this->json('DELETE', "/api/v1/value-lists/{$vanceRefrigeration->id}")->assertNotFound();

        $this->assertNotSoftDeleted($vanceRefrigeration);
    }
}
