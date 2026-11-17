<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Api;

use App\Models\User;
use App\Models\ValueList;
use App\Models\ValueListItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
                    ['id' => $departments->items()->where('label', 'Sales')->value('id'), 'label' => 'Sales'],
                    ['id' => $departments->items()->where('label', 'Accounting')->value('id'), 'label' => 'Accounting'],
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
}
