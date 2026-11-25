<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Api;

use App\Models\User;
use App\Models\ValueList;
use App\Models\ValueListItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ValueListItemPositionControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_moves_a_value_in_its_list(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $suppliers = ValueList::factory()->create(['account_id' => $user->account_id]);
        ValueListItem::factory()->create(['account_id' => $user->account_id, 'value_list_id' => $suppliers->id, 'label' => 'Staples', 'position' => 1]);
        $officeDepot = ValueListItem::factory()->create(['account_id' => $user->account_id, 'value_list_id' => $suppliers->id, 'label' => 'Office Depot', 'position' => 2]);

        Sanctum::actingAs($user);

        $this->json('PUT', "/api/v1/value-lists/{$suppliers->id}/items/{$officeDepot->id}/position", ['position' => 1])
            ->assertOk()
            ->assertJsonPath('data.attributes.position', 1);
    }

    #[Test]
    public function it_refuses_a_position_below_one(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $suppliers = ValueList::factory()->create(['account_id' => $user->account_id]);
        $staples = ValueListItem::factory()->create(['account_id' => $user->account_id, 'value_list_id' => $suppliers->id]);

        Sanctum::actingAs($user);

        $this->json('PUT', "/api/v1/value-lists/{$suppliers->id}/items/{$staples->id}/position", ['position' => 0])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('position');
    }

    #[Test]
    public function it_does_not_find_a_value_of_another_account(): void
    {
        Queue::fake();

        $list = ValueList::factory()->create();
        $item = ValueListItem::factory()->create(['account_id' => $list->account_id, 'value_list_id' => $list->id]);

        Sanctum::actingAs(User::factory()->create());

        $this->json('PUT', "/api/v1/value-lists/{$list->id}/items/{$item->id}/position", ['position' => 1])->assertNotFound();
    }
}
