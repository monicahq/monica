<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\MonicaServer;
use App\Mcp\Tools\ListPossessionTags;
use App\Models\PossessionTag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ListPossessionTagsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_lists_the_tags_of_the_account(): void
    {
        $user = User::factory()->create();
        PossessionTag::factory()->create(['account_id' => $user->account_id, 'name' => 'Insured']);
        PossessionTag::factory()->create(['name' => 'Dundie award']);

        MonicaServer::actingAs($user)->tool(ListPossessionTags::class)
            ->assertOk()
            ->assertHasNoErrors()
            ->assertSee('Insured')
            ->assertDontSee('Dundie award');
    }
}
