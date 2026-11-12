<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Api;

use App\Models\SupportRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SupportMessageControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_writes_in_a_request(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $supportRequest = SupportRequest::factory()->waitingOnUser()->create(['account_id' => $user->account_id]);

        Sanctum::actingAs($user);

        $response = $this->json('POST', "/api/v1/support-requests/{$supportRequest->uuid}/messages", ['body' => 'Settings show New York.']);

        $response->assertCreated();
        $response->assertJsonStructure(['data' => ['type', 'id', 'attributes' => ['kind', 'author_name', 'is_support_team', 'body', 'attachments', 'created_at']]]);
        $response->assertJsonPath('data.attributes.kind', 'message');
    }

    #[Test]
    public function it_answers_not_found_for_a_request_of_another_account(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->json('POST', '/api/v1/support-requests/'.SupportRequest::factory()->create()->uuid.'/messages', ['body' => 'Is this Stamford?'])
            ->assertNotFound();
    }
}
