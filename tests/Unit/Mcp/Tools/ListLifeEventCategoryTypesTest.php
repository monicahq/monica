<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Tools\ListLifeEventCategoryTypes;
use App\Models\LifeEventCategory;
use App\Models\LifeEventType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Mcp\Request;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ListLifeEventCategoryTypesTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_lists_the_types_of_a_category_by_position(): void
    {
        $user = User::factory()->create();
        $office = LifeEventCategory::factory()->create(['account_id' => $user->account_id]);
        $warehouse = LifeEventCategory::factory()->create(['account_id' => $user->account_id]);
        LifeEventType::factory()->create(['account_id' => $user->account_id, 'life_event_category_id' => $office->id, 'name' => 'Got promoted', 'position' => 2]);
        LifeEventType::factory()->create(['account_id' => $user->account_id, 'life_event_category_id' => $office->id, 'name' => 'Won a Dundie', 'position' => 1]);
        LifeEventType::factory()->create(['account_id' => $user->account_id, 'life_event_category_id' => $warehouse->id, 'name' => 'Drove the forklift']);

        $this->actingAs($user);

        $response = app(ListLifeEventCategoryTypes::class)->handle(new Request(['life_event_category' => $office->uuid]));

        $this->assertSame(
            ['Won a Dundie', 'Got promoted'],
            array_column(array_column(json_decode((string) $response->content(), true), 'attributes'), 'name'),
        );
    }

    #[Test]
    public function it_names_the_api_capability_it_mirrors(): void
    {
        $this->assertSame('api.v1.lifeEventCategories.types', app(ListLifeEventCategoryTypes::class)->mirrors());
    }
}
