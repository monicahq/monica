<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\EricaServer;
use App\Mcp\Tools\CreateGroup;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CreateGroupTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_creates_a_group(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        EricaServer::actingAs($user)->tool(CreateGroup::class, ['vault' => $vault->uuid, 'name' => 'Party planning committee'])
            ->assertOk()
            ->assertHasNoErrors();

        $this->assertDatabaseHas('groups', ['vault_id' => $vault->id, 'name' => 'Party planning committee']);
    }

    #[Test]
    public function it_names_the_api_capability_it_mirrors(): void
    {
        $this->assertSame('api.v1.groups.create', app(CreateGroup::class)->mirrors());
    }
}
