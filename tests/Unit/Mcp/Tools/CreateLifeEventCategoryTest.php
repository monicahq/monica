<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\MonicaServer;
use App\Mcp\Tools\CreateLifeEventCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CreateLifeEventCategoryTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_creates_a_life_event_category(): void
    {
        $user = User::factory()->create();

        MonicaServer::actingAs($user)->tool(CreateLifeEventCategory::class, ['name' => 'Sales'])
            ->assertOk()
            ->assertHasNoErrors();

        $this->assertDatabaseHas('life_event_categories', [
            'account_id' => $user->account_id,
            'name' => 'Sales',
            'is_system' => false,
        ]);
    }
}
