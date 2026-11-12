<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\EricaServer;
use App\Mcp\Tools\UpdateLifeEventCategory;
use App\Models\LifeEventCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpdateLifeEventCategoryTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_renames_a_life_event_category(): void
    {
        $user = User::factory()->create();
        $category = LifeEventCategory::factory()->create(['account_id' => $user->account_id]);

        EricaServer::actingAs($user)->tool(UpdateLifeEventCategory::class, [
            'life_event_category' => $category->uuid,
            'name' => 'Sales',
        ])->assertOk()->assertHasNoErrors();

        $this->assertSame('Sales', $category->refresh()->name);
    }
}
