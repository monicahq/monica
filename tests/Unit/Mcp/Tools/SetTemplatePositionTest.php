<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\EricaServer;
use App\Mcp\Tools\SetTemplatePosition;
use App\Models\Template;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SetTemplatePositionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_moves_a_template_in_the_list(): void
    {
        $user = User::factory()->create();
        $first = Template::factory()->create(['account_id' => $user->account_id, 'position' => 1]);
        $second = Template::factory()->create(['account_id' => $user->account_id, 'position' => 2]);

        EricaServer::actingAs($user)->tool(SetTemplatePosition::class, ['template' => $second->uuid, 'position' => 1])
            ->assertOk()
            ->assertHasNoErrors();

        $this->assertSame(1, $second->refresh()->position);
        $this->assertSame(2, $first->refresh()->position);
    }
}
