<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\EricaServer;
use App\Mcp\Tools\CreateLifeEventType;
use App\Models\LifeEventCategory;
use App\Models\LifeEventType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CreateLifeEventTypeTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_creates_a_life_event_type(): void
    {
        $user = User::factory()->create();
        $category = LifeEventCategory::factory()->create(['account_id' => $user->account_id]);

        EricaServer::actingAs($user)->tool(CreateLifeEventType::class, [
            'life_event_category_id' => $category->uuid,
            'name' => 'Won a Dundie',
            'description' => 'An award handed out at Chili’s.',
            'icon' => 'briefcase',
            'colour' => '#4f8a49',
        ])->assertOk()->assertHasNoErrors();

        $this->assertDatabaseHas('life_event_types', [
            'account_id' => $user->account_id,
            'life_event_category_id' => $category->id,
            'name' => 'Won a Dundie',
            'icon' => 'briefcase',
            'colour' => '#4f8a49',
        ]);
    }

    #[Test]
    public function it_refuses_a_category_of_another_account(): void
    {
        $user = User::factory()->create();
        $category = LifeEventCategory::factory()->create();

        EricaServer::actingAs($user)->tool(CreateLifeEventType::class, [
            'life_event_category_id' => $category->uuid,
            'name' => 'Won a Dundie',
        ])->assertHasErrors(['That life event category does not exist.']);

        $this->assertSame(0, LifeEventType::query()->count());
    }
}
