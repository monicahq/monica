<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\MonicaServer;
use App\Mcp\Tools\DeletePossessionTag;
use App\Models\PossessionTag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DeletePossessionTagTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_deletes_a_tag(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $possessionTag = PossessionTag::factory()->create(['account_id' => $user->account_id]);

        MonicaServer::actingAs($user)->tool(DeletePossessionTag::class, ['possession_tag' => $possessionTag->uuid])
            ->assertOk()
            ->assertHasNoErrors();

        $this->assertModelMissing($possessionTag);
    }
}
