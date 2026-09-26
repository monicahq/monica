<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Jobs\LogUserAction;
use App\Mcp\Servers\EricaServer;
use App\Mcp\Tools\SetLifeEventCategoryActivation;
use App\Models\LifeEventCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SetLifeEventCategoryActivationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_switches_a_life_event_category_off(): void
    {
        $user = User::factory()->create();
        $category = LifeEventCategory::factory()->create(['account_id' => $user->account_id]);

        EricaServer::actingAs($user)->tool(SetLifeEventCategoryActivation::class, [
            'life_event_category' => $category->uuid,
            'is_active' => false,
        ])->assertOk()->assertHasNoErrors();

        $this->assertFalse($category->refresh()->is_active);
    }

    #[Test]
    public function it_switches_a_life_event_category_on(): void
    {
        $user = User::factory()->create();
        $category = LifeEventCategory::factory()->inactive()->create(['account_id' => $user->account_id]);

        EricaServer::actingAs($user)->tool(SetLifeEventCategoryActivation::class, [
            'life_event_category' => $category->uuid,
            'is_active' => true,
        ])->assertOk()->assertHasNoErrors();

        $this->assertTrue($category->refresh()->is_active);
    }

    #[Test]
    public function it_journals_nothing_when_the_category_is_already_in_that_state(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $category = LifeEventCategory::factory()->create(['account_id' => $user->account_id]);

        EricaServer::actingAs($user)->tool(SetLifeEventCategoryActivation::class, [
            'life_event_category' => $category->uuid,
            'is_active' => true,
        ])->assertOk()->assertHasNoErrors();

        Queue::assertNotPushed(LogUserAction::class);
    }

    #[Test]
    public function it_names_the_api_capability_it_mirrors(): void
    {
        $this->assertSame('api.v1.lifeEventCategories.activation.update', app(SetLifeEventCategoryActivation::class)->mirrors());
    }
}
