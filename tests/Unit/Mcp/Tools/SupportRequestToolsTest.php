<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\MonicaServer;
use App\Mcp\Tools\AddSupportMessage;
use App\Mcp\Tools\CreateSupportRequest;
use App\Mcp\Tools\GetSupportRequest;
use App\Mcp\Tools\ListSupportRequests;
use App\Mcp\Tools\SolveSupportRequest;
use App\Models\Administrator;
use App\Models\SupportRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SupportRequestToolsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_offers_none_of_its_tools_while_support_is_switched_off(): void
    {
        config(['monica.support_enabled' => false]);
        Administrator::factory()->create(['user_id' => User::factory()->create()->id]);
        $user = User::factory()->create();
        $supportRequest = SupportRequest::factory()->create(['account_id' => $user->account_id]);

        MonicaServer::actingAs($user)->tool(ListSupportRequests::class, [])->assertHasErrors();
        MonicaServer::actingAs($user)->tool(GetSupportRequest::class, ['support_request' => $supportRequest->uuid])->assertHasErrors();
        MonicaServer::actingAs($user)->tool(CreateSupportRequest::class, ['category' => 'question', 'subject' => 'Printer', 'body' => 'The printer is on fire again.'])->assertHasErrors();
        MonicaServer::actingAs($user)->tool(AddSupportMessage::class, ['support_request' => $supportRequest->uuid, 'body' => 'Still on fire.'])->assertHasErrors();
        MonicaServer::actingAs($user)->tool(SolveSupportRequest::class, ['support_request' => $supportRequest->uuid])->assertHasErrors();
    }
}
