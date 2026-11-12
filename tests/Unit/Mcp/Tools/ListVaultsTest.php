<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\MonicaServer;
use App\Mcp\Tools\ListVaults;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ListVaultsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_answers_the_vaults_of_the_account(): void
    {
        $user = User::factory()->create();
        Vault::factory()->create(['account_id' => $user->account_id, 'name' => 'Dunder Mifflin']);

        MonicaServer::actingAs($user)->tool(ListVaults::class)
            ->assertOk()
            ->assertHasNoErrors()
            ->assertSee('Dunder Mifflin');
    }
}
