<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\EricaServer;
use App\Mcp\Tools\UpdateLifeEventType;
use App\Models\LifeEventCategory;
use App\Models\LifeEventType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpdateLifeEventTypeTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_changes_only_what_it_is_given(): void
    {
        $user = User::factory()->create();
        $type = LifeEventType::factory()->inactive()->create([
            'account_id' => $user->account_id,
            'life_event_category_id' => LifeEventCategory::factory()->create(['account_id' => $user->account_id])->id,
            'name' => 'Won a Dundie',
            'description' => 'An award handed out at Chili’s.',
            'icon' => 'briefcase',
        ]);

        EricaServer::actingAs($user)->tool(UpdateLifeEventType::class, [
            'life_event_type' => $type->uuid,
            'colour' => '#4f8a49',
        ])->assertOk()->assertHasNoErrors();

        $category = $type->life_event_category_id;
        $type->refresh();

        $this->assertSame('#4f8a49', $type->colour);
        $this->assertSame('Won a Dundie', $type->name);
        $this->assertSame('An award handed out at Chili’s.', $type->description);
        $this->assertSame('briefcase', $type->icon);
        $this->assertSame($category, $type->life_event_category_id);
        $this->assertFalse($type->is_active);
    }

    #[Test]
    public function it_clears_the_description_when_given_null(): void
    {
        $user = User::factory()->create();
        $type = LifeEventType::factory()->create([
            'account_id' => $user->account_id,
            'life_event_category_id' => LifeEventCategory::factory()->create(['account_id' => $user->account_id])->id,
            'description' => 'An award handed out at Chili’s.',
        ]);

        EricaServer::actingAs($user)->tool(UpdateLifeEventType::class, [
            'life_event_type' => $type->uuid,
            'description' => null,
        ])->assertOk()->assertHasNoErrors();

        $this->assertNull($type->refresh()->description);
    }

    #[Test]
    public function it_moves_a_type_to_another_category(): void
    {
        $user = User::factory()->create();
        $type = LifeEventType::factory()->create(['account_id' => $user->account_id]);
        $category = LifeEventCategory::factory()->create(['account_id' => $user->account_id]);

        EricaServer::actingAs($user)->tool(UpdateLifeEventType::class, [
            'life_event_type' => $type->uuid,
            'life_event_category_id' => $category->uuid,
        ])->assertOk()->assertHasNoErrors();

        $this->assertSame($category->id, $type->refresh()->life_event_category_id);
    }
}
