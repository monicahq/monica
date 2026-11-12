<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Api\Admin;

use App\Models\Administrator;
use App\Models\SupportMessage;
use App\Models\SupportRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SupportRequestControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_lists_the_queue_with_a_count_per_view(): void
    {
        SupportRequest::factory()->toHandle()->create();
        SupportRequest::factory()->waitingOnUser()->create();

        $response = $this->withToken($this->administratorKey(['*', 'administration']))->json('GET', '/api/v1/admin/support-requests');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('meta.counts', ['to_handle' => 1, 'waiting_on_user' => 1, 'solved' => 0, 'all' => 2]);
    }

    #[Test]
    public function it_shows_a_request_with_its_notes_and_its_history(): void
    {
        $supportRequest = SupportRequest::factory()->create();
        SupportMessage::factory()->note()->create(['support_request_id' => $supportRequest->id, 'body' => 'Check the domain reputation first.']);

        $response = $this->withToken($this->administratorKey(['*', 'administration']))->json('GET', "/api/v1/admin/support-requests/{$supportRequest->uuid}");

        $response->assertOk();
        $response->assertJsonStructure(['data' => ['attributes' => ['messages', 'context', 'status_changes', 'user' => ['name', 'email']]]]);
        $response->assertJsonPath('data.attributes.messages.0.attributes.kind', 'note');
    }

    #[Test]
    public function it_replies_adds_a_note_and_moves_a_request(): void
    {
        Queue::fake();
        Notification::fake();
        $supportRequest = SupportRequest::factory()->toHandle()->create();
        $key = $this->administratorKey(['*', 'administration']);

        $this->withToken($key)->json('POST', "/api/v1/admin/support-requests/{$supportRequest->uuid}/replies", ['body' => 'Could you send the file?'])
            ->assertCreated()
            ->assertJsonPath('data.attributes.kind', 'reply');
        $this->withToken($key)->json('POST', "/api/v1/admin/support-requests/{$supportRequest->uuid}/notes", ['body' => 'Probably the photo.'])
            ->assertCreated()
            ->assertJsonPath('data.attributes.kind', 'note');
        $this->withToken($key)->json('PUT', "/api/v1/admin/support-requests/{$supportRequest->uuid}/status", ['status' => 'solved'])
            ->assertOk()
            ->assertJsonPath('data.attributes.status', 'solved');
    }

    #[Test]
    public function it_is_closed_to_a_key_without_the_administration_scope(): void
    {
        $this->withToken($this->administratorKey(['*']))->json('GET', '/api/v1/admin/support-requests')->assertNotFound();
    }

    #[Test]
    public function it_is_closed_to_somebody_who_does_not_run_the_instance(): void
    {
        $dwight = User::factory()->create();

        $this->withToken($dwight->createToken('Dwight’s script', ['*', 'administration'])->plainTextToken)
            ->json('GET', '/api/v1/admin/support-requests')
            ->assertNotFound();
    }

    /** @param  list<string>  $abilities */
    private function administratorKey(array $abilities): string
    {
        $user = User::factory()->create();
        Administrator::factory()->create(['user_id' => $user->id]);

        return $user->createToken('Support desk', $abilities)->plainTextToken;
    }
}
