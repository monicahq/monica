<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\EricaServer;
use App\Mcp\Tools\DeleteLifeEventCategory;
use App\Models\LifeEventCategory;
use App\Models\LifeEventType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DeleteLifeEventCategoryTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_deletes_an_empty_life_event_category(): void
    {
        $user = User::factory()->create();
        $category = LifeEventCategory::factory()->create(['account_id' => $user->account_id]);

        EricaServer::actingAs($user)->tool(DeleteLifeEventCategory::class, ['life_event_category' => $category->uuid])
            ->assertOk()
            ->assertHasNoErrors();

        $this->assertSoftDeleted($category);
    }

    #[Test]
    public function it_refuses_a_category_still_holding_types(): void
    {
        $user = User::factory()->create();
        $category = LifeEventCategory::factory()->create(['account_id' => $user->account_id]);
        LifeEventType::factory()->create(['account_id' => $user->account_id, 'life_event_category_id' => $category->id]);

        EricaServer::actingAs($user)->tool(DeleteLifeEventCategory::class, ['life_event_category' => $category->uuid])
            ->assertHasErrors(['Types are filed under this category, so it stays.']);

        $this->assertNotSoftDeleted($category);
    }
}
