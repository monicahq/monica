<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\MonicaServer;
use App\Mcp\Tools\CreateValueListItem;
use App\Mcp\Tools\DeleteValueList;
use App\Mcp\Tools\DeleteValueListItem;
use App\Mcp\Tools\SetValueListItemActivation;
use App\Mcp\Tools\SetValueListItemPosition;
use App\Mcp\Tools\UpdateValueList;
use App\Mcp\Tools\UpdateValueListItem;
use App\Models\User;
use App\Models\ValueList;
use App\Models\ValueListItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ValueListToolsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_creates_a_value_in_a_value_list(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $suppliers = ValueList::factory()->create(['account_id' => $user->account_id, 'name' => 'Paper suppliers']);

        MonicaServer::actingAs($user)->tool(CreateValueListItem::class, [
            'value_list' => $suppliers->id,
            'label' => 'Staples',
        ])->assertOk()->assertHasNoErrors();

        $this->assertDatabaseHas('value_list_items', ['value_list_id' => $suppliers->id, 'label' => 'Staples']);
    }

    #[Test]
    public function it_renames_a_value_list(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $suppliers = ValueList::factory()->create(['account_id' => $user->account_id, 'name' => 'Paper suppliers']);

        MonicaServer::actingAs($user)->tool(UpdateValueList::class, [
            'value_list' => $suppliers->id,
            'name' => 'Dunder Mifflin rivals',
        ])->assertOk()->assertHasNoErrors();

        $this->assertDatabaseHas('value_lists', ['id' => $suppliers->id, 'name' => 'Dunder Mifflin rivals']);
    }

    #[Test]
    public function it_deletes_a_value_list(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $suppliers = ValueList::factory()->create(['account_id' => $user->account_id]);

        MonicaServer::actingAs($user)->tool(DeleteValueList::class, ['value_list' => $suppliers->id])
            ->assertOk()
            ->assertHasNoErrors();

        $this->assertSoftDeleted($suppliers);
    }

    #[Test]
    public function it_renames_a_value_of_a_value_list(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $suppliers = ValueList::factory()->create(['account_id' => $user->account_id]);
        $staples = ValueListItem::factory()->create(['account_id' => $user->account_id, 'value_list_id' => $suppliers->id, 'label' => 'Staples']);

        MonicaServer::actingAs($user)->tool(UpdateValueListItem::class, [
            'value_list' => $suppliers->id,
            'value_list_item' => $staples->id,
            'label' => 'Prince Family Paper',
        ])->assertOk()->assertHasNoErrors();

        $this->assertDatabaseHas('value_list_items', ['id' => $staples->id, 'label' => 'Prince Family Paper']);
    }

    #[Test]
    public function it_stops_offering_a_value_of_a_value_list(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $suppliers = ValueList::factory()->create(['account_id' => $user->account_id]);
        $staples = ValueListItem::factory()->create(['account_id' => $user->account_id, 'value_list_id' => $suppliers->id]);

        MonicaServer::actingAs($user)->tool(SetValueListItemActivation::class, [
            'value_list' => $suppliers->id,
            'value_list_item' => $staples->id,
            'is_active' => false,
        ])->assertOk()->assertHasNoErrors();

        $this->assertDatabaseHas('value_list_items', ['id' => $staples->id, 'is_active' => false]);
    }

    #[Test]
    public function it_moves_a_value_of_a_value_list(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $suppliers = ValueList::factory()->create(['account_id' => $user->account_id]);
        ValueListItem::factory()->create(['account_id' => $user->account_id, 'value_list_id' => $suppliers->id, 'position' => 1]);
        $staples = ValueListItem::factory()->create(['account_id' => $user->account_id, 'value_list_id' => $suppliers->id, 'position' => 2]);

        MonicaServer::actingAs($user)->tool(SetValueListItemPosition::class, [
            'value_list' => $suppliers->id,
            'value_list_item' => $staples->id,
            'position' => 1,
        ])->assertOk()->assertHasNoErrors();

        $this->assertDatabaseHas('value_list_items', ['id' => $staples->id, 'position' => 1]);
    }

    #[Test]
    public function it_deletes_a_value_of_a_value_list(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $suppliers = ValueList::factory()->create(['account_id' => $user->account_id]);
        $staples = ValueListItem::factory()->create(['account_id' => $user->account_id, 'value_list_id' => $suppliers->id]);

        MonicaServer::actingAs($user)->tool(DeleteValueListItem::class, [
            'value_list' => $suppliers->id,
            'value_list_item' => $staples->id,
        ])->assertOk()->assertHasNoErrors();

        $this->assertSoftDeleted($staples);
    }
}
