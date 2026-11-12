<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\EricaServer;
use App\Mcp\Tools\ListLifeEventCategories;
use App\Models\LifeEventCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ListLifeEventCategoriesTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_answers_what_the_account_holds(): void
    {
        $user = User::factory()->create();
        $mine = LifeEventCategory::factory()->create(['account_id' => $user->account_id]);

        EricaServer::actingAs($user)->tool(ListLifeEventCategories::class)
            ->assertOk()
            ->assertHasNoErrors()
            ->assertSee($mine->uuid);
    }

    #[Test]
    public function it_leaves_out_what_belongs_to_another_account(): void
    {
        $user = User::factory()->create();
        $theirs = LifeEventCategory::factory()->create();

        EricaServer::actingAs($user)->tool(ListLifeEventCategories::class)
            ->assertOk()
            ->assertDontSee($theirs->uuid);
    }
}
