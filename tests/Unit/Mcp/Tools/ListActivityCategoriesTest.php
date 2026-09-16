<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\EricaServer;
use App\Mcp\Tools\ListActivityCategories;
use App\Models\ActivityCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ListActivityCategoriesTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_answers_what_the_account_holds(): void
    {
        $user = User::factory()->create();
        $mine = ActivityCategory::factory()->create(['account_id' => $user->account_id]);

        EricaServer::actingAs($user)->tool(ListActivityCategories::class)
            ->assertOk()
            ->assertHasNoErrors()
            ->assertSee($mine->uuid);
    }

    #[Test]
    public function it_leaves_out_what_belongs_to_another_account(): void
    {
        $user = User::factory()->create();
        $theirs = ActivityCategory::factory()->create();

        EricaServer::actingAs($user)->tool(ListActivityCategories::class)
            ->assertOk()
            ->assertDontSee($theirs->uuid);
    }

    #[Test]
    public function it_answers_an_account_holding_nothing_without_erroring(): void
    {
        $user = User::factory()->create();
        $user->account->activityCategories()->delete();

        EricaServer::actingAs($user)->tool(ListActivityCategories::class)
            ->assertOk()
            ->assertHasNoErrors();
    }

    #[Test]
    public function it_names_the_api_capability_it_mirrors(): void
    {
        $this->assertSame('api.v1.activityCategories', app(ListActivityCategories::class)->mirrors());
    }
}
