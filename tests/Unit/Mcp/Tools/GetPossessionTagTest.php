<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\MonicaServer;
use App\Mcp\Tools\GetPossessionTag;
use App\Models\PossessionTag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GetPossessionTagTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_reads_a_tag(): void
    {
        $user = User::factory()->create();
        $possessionTag = PossessionTag::factory()->create(['account_id' => $user->account_id, 'name' => 'Fragile']);

        MonicaServer::actingAs($user)->tool(GetPossessionTag::class, ['possession_tag' => $possessionTag->uuid])
            ->assertOk()
            ->assertHasNoErrors()
            ->assertSee('Fragile');
    }

    #[Test]
    public function it_reports_a_tag_of_another_account_as_not_existing(): void
    {
        $possessionTag = PossessionTag::factory()->create();

        MonicaServer::actingAs(User::factory()->create())->tool(GetPossessionTag::class, ['possession_tag' => $possessionTag->uuid])
            ->assertHasErrors();
    }
}
