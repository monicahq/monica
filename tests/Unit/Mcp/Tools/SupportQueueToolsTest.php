<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\EricaServer;
use App\Mcp\Tools\AddSupportNote;
use App\Mcp\Tools\ChangeSupportRequestStatus;
use App\Mcp\Tools\GetSupportQueueRequest;
use App\Mcp\Tools\ListSupportQueue;
use App\Mcp\Tools\SendSupportReply;
use App\Models\Administrator;
use App\Models\SupportMessage;
use App\Models\SupportRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\PersonalAccessToken;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SupportQueueToolsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_lets_an_administrator_key_work_the_queue_of_every_account(): void
    {
        Queue::fake();
        Notification::fake();
        $supportRequest = SupportRequest::factory()->toHandle()->create(['subject' => 'vCard import stops at 50']);
        SupportMessage::factory()->note()->create(['support_request_id' => $supportRequest->id, 'body' => 'Probably the embedded photo.']);
        $administrator = $this->holding(['*', 'administration'], administrator: true);

        EricaServer::actingAs($administrator)->tool(ListSupportQueue::class, [])->assertOk()->assertSee('vCard import stops at 50');
        EricaServer::actingAs($administrator)->tool(GetSupportQueueRequest::class, ['support_request' => $supportRequest->uuid])->assertOk()->assertSee('Probably the embedded photo.');
        EricaServer::actingAs($administrator)->tool(AddSupportNote::class, ['support_request' => $supportRequest->uuid, 'body' => 'Asked for the file.'])->assertOk();
        EricaServer::actingAs($administrator)->tool(SendSupportReply::class, ['support_request' => $supportRequest->uuid, 'body' => 'Could you send the file?'])->assertOk();
        EricaServer::actingAs($administrator)->tool(ChangeSupportRequestStatus::class, ['support_request' => $supportRequest->uuid, 'status' => 'solved'])->assertOk();

        $this->assertEquals('solved', $supportRequest->refresh()->status->value);
    }

    #[Test]
    public function it_keeps_the_queue_closed_to_a_key_without_the_administration_scope(): void
    {
        $administrator = $this->holding(['*'], administrator: true);

        EricaServer::actingAs($administrator)->tool(ListSupportQueue::class, [])->assertHasErrors();
    }

    #[Test]
    public function it_keeps_the_queue_closed_while_support_is_switched_off(): void
    {
        config(['monica.support_enabled' => false]);
        $administrator = $this->holding(['*', 'administration'], administrator: true);

        EricaServer::actingAs($administrator)->tool(ListSupportQueue::class, [])->assertHasErrors();
    }

    #[Test]
    public function it_keeps_the_queue_closed_to_somebody_who_does_not_run_the_instance(): void
    {
        $dwight = $this->holding(['*', 'administration'], administrator: false);

        EricaServer::actingAs($dwight)->tool(ListSupportQueue::class, [])->assertHasErrors();
    }

    #[Test]
    public function it_names_the_api_capabilities_it_mirrors(): void
    {
        $this->assertSame('api.v1.admin.supportRequests', app(ListSupportQueue::class)->mirrors());
        $this->assertSame('api.v1.admin.supportRequests.show', app(GetSupportQueueRequest::class)->mirrors());
        $this->assertSame('api.v1.admin.supportRequests.replies.create', app(SendSupportReply::class)->mirrors());
        $this->assertSame('api.v1.admin.supportRequests.notes.create', app(AddSupportNote::class)->mirrors());
        $this->assertSame('api.v1.admin.supportRequests.status.update', app(ChangeSupportRequestStatus::class)->mirrors());
    }

    /** @param  list<string>  $abilities */
    private function holding(array $abilities, bool $administrator): User
    {
        $user = User::factory()->create();

        if ($administrator) {
            Administrator::factory()->create(['user_id' => $user->id]);
        }

        $plainText = $user->createToken('Support desk', $abilities)->plainTextToken;

        return $user->withAccessToken(PersonalAccessToken::findToken($plainText));
    }
}
