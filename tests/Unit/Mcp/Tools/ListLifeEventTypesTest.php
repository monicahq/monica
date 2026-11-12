<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\MonicaServer;
use App\Mcp\Tools\ListLifeEventTypes;
use App\Models\LifeEventCategory;
use App\Models\LifeEventType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Mcp\Request;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ListLifeEventTypesTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_lists_the_types_by_category_then_position(): void
    {
        $user = User::factory()->create();
        $sales = LifeEventCategory::factory()->create(['account_id' => $user->account_id, 'position' => 2]);
        $office = LifeEventCategory::factory()->create(['account_id' => $user->account_id, 'position' => 1]);
        LifeEventType::factory()->create(['account_id' => $user->account_id, 'life_event_category_id' => $sales->id, 'name' => 'Closed a deal', 'position' => 1]);
        LifeEventType::factory()->create(['account_id' => $user->account_id, 'life_event_category_id' => $office->id, 'name' => 'Won a Dundie', 'position' => 2]);
        LifeEventType::factory()->create(['account_id' => $user->account_id, 'life_event_category_id' => $office->id, 'name' => 'Joined the party planning committee', 'position' => 1]);

        $this->actingAs($user);

        $response = app(ListLifeEventTypes::class)->handle(new Request([]));

        $this->assertSame(
            ['Joined the party planning committee', 'Won a Dundie', 'Closed a deal'],
            array_column(array_column(json_decode((string) $response->content(), true), 'attributes'), 'name'),
        );
    }

    #[Test]
    public function it_leaves_out_the_types_of_another_account(): void
    {
        $user = User::factory()->create();
        LifeEventType::factory()->create(['name' => 'Opened a paper company']);

        MonicaServer::actingAs($user)->tool(ListLifeEventTypes::class)
            ->assertOk()
            ->assertDontSee('Opened a paper company');
    }
}
