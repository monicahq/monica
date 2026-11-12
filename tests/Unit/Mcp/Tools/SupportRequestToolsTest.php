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
use App\Models\SupportMessage;
use App\Models\SupportRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SupportRequestToolsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_opens_a_request_for_the_user(): void
    {
        Queue::fake();
        Notification::fake();
        Administrator::factory()->create(['user_id' => User::factory()->create()->id]);
        $user = User::factory()->create();

        MonicaServer::actingAs($user)->tool(CreateSupportRequest::class, [
            'category' => 'question',
            'subject' => 'Can I share one contact without sharing the vault?',
            'body' => 'I want Holly to see Toby’s page but nothing else.',
        ])->assertOk()->assertHasNoErrors();

        $this->assertDatabaseHas('support_requests', ['account_id' => $user->account_id, 'status' => 'to_handle']);
    }

    #[Test]
    public function it_lists_and_reads_the_requests_of_the_account_without_the_notes(): void
    {
        $user = User::factory()->create();
        $supportRequest = SupportRequest::factory()->create(['account_id' => $user->account_id, 'subject' => 'Late reminders']);
        SupportMessage::factory()->note()->create(['support_request_id' => $supportRequest->id, 'body' => 'Toby set the time zone wrong again.']);
        SupportRequest::factory()->create(['subject' => 'Stamford branch closing']);

        MonicaServer::actingAs($user)->tool(ListSupportRequests::class, [])
            ->assertOk()
            ->assertSee('Late reminders')
            ->assertDontSee('Stamford branch closing');

        MonicaServer::actingAs($user)->tool(GetSupportRequest::class, ['support_request' => $supportRequest->uuid])
            ->assertOk()
            ->assertDontSee('Toby set the time zone wrong again.');
    }

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

    #[Test]
    public function it_does_not_find_a_request_of_another_account(): void
    {
        MonicaServer::actingAs(User::factory()->create())->tool(GetSupportRequest::class, ['support_request' => SupportRequest::factory()->create()->uuid])
            ->assertHasErrors(['That support request does not exist.']);
    }

    #[Test]
    public function it_writes_in_a_request_and_marks_it_solved(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $supportRequest = SupportRequest::factory()->waitingOnUser()->create(['account_id' => $user->account_id]);

        MonicaServer::actingAs($user)->tool(AddSupportMessage::class, ['support_request' => $supportRequest->uuid, 'body' => 'Settings show New York.'])
            ->assertOk();
        MonicaServer::actingAs($user)->tool(SolveSupportRequest::class, ['support_request' => $supportRequest->uuid])
            ->assertOk();

        $this->assertEquals('solved', $supportRequest->refresh()->status->value);
    }

    #[Test]
    public function it_names_the_api_capabilities_it_mirrors(): void
    {
        $this->assertSame('api.v1.supportRequests', app(ListSupportRequests::class)->mirrors());
        $this->assertSame('api.v1.supportRequests.show', app(GetSupportRequest::class)->mirrors());
        $this->assertSame('api.v1.supportRequests.create', app(CreateSupportRequest::class)->mirrors());
        $this->assertSame('api.v1.supportRequests.messages.create', app(AddSupportMessage::class)->mirrors());
        $this->assertSame('api.v1.supportRequests.solution.create', app(SolveSupportRequest::class)->mirrors());
    }
}
