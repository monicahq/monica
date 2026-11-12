<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\EricaServer;
use App\Mcp\Tools\GetLifeEventCategory;
use App\Models\LifeEventCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GetLifeEventCategoryTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_returns_a_life_event_category(): void
    {
        $user = User::factory()->create();
        $category = LifeEventCategory::factory()->create(['account_id' => $user->account_id, 'name' => 'Sales']);

        EricaServer::actingAs($user)->tool(GetLifeEventCategory::class, ['life_event_category' => $category->uuid])
            ->assertOk()
            ->assertSee('Sales');
    }

    #[Test]
    public function it_reports_a_life_event_category_of_another_account_as_not_existing(): void
    {
        $user = User::factory()->create();
        $category = LifeEventCategory::factory()->create();

        EricaServer::actingAs($user)->tool(GetLifeEventCategory::class, ['life_event_category' => $category->uuid])
            ->assertHasErrors(['That life event category does not exist.']);
    }
}
