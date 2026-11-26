<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Api;

use App\Models\Administrator;
use App\Models\SupportAttachment;
use App\Models\SupportMessage;
use App\Models\SupportRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SupportRequestControllerTest extends TestCase
{
    use RefreshDatabase;

    private array $jsonStructure = [
        'type',
        'id',
        'attributes' => [
            'reference',
            'category',
            'subject',
            'status',
            'status_label',
            'opened_at',
            'last_activity_at',
            'solved_at',
        ],
        'links' => ['self'],
    ];

    #[Test]
    public function it_lists_only_the_requests_of_the_account(): void
    {
        $user = User::factory()->create();
        $mine = SupportRequest::factory()->create(['account_id' => $user->account_id]);
        SupportRequest::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->json('GET', '/api/v1/support-requests');

        $response->assertOk();
        $response->assertJsonStructure(['data' => ['*' => $this->jsonStructure], 'meta' => ['page', 'per_page', 'total']]);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.id', $mine->uuid);
    }

    #[Test]
    public function it_shows_a_request_without_its_internal_notes(): void
    {
        $user = User::factory()->create();
        $supportRequest = SupportRequest::factory()->create(['account_id' => $user->account_id]);
        SupportMessage::factory()->create(['support_request_id' => $supportRequest->id, 'body' => 'Jan’s reminder came on the 4th.']);
        SupportMessage::factory()->note()->create(['support_request_id' => $supportRequest->id, 'body' => 'Toby set the time zone wrong again.']);

        Sanctum::actingAs($user);

        $response = $this->json('GET', "/api/v1/support-requests/{$supportRequest->uuid}");

        $response->assertOk();
        $response->assertJsonStructure(['data' => $this->jsonStructure]);
        $response->assertJsonCount(1, 'data.attributes.messages');
        $response->assertJsonMissing(['body' => 'Toby set the time zone wrong again.']);
    }

    #[Test]
    public function it_answers_not_found_for_a_request_of_another_account(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->json('GET', '/api/v1/support-requests/'.SupportRequest::factory()->create()->uuid)->assertNotFound();
    }

    #[Test]
    public function it_opens_a_request_with_a_file(): void
    {
        Queue::fake();
        Notification::fake();
        Storage::fake();
        Administrator::factory()->create(['user_id' => User::factory()->create()->id]);
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->post('/api/v1/support-requests', [
            'category' => 'billing',
            'subject' => 'October invoice shows the wrong company name',
            'body' => 'The invoice still says Dunder Mifflin Paper Company.',
            'files' => [UploadedFile::fake()->create('invoice-october.pdf', 88, 'application/pdf')],
        ], ['Accept' => 'application/json']);

        $response->assertCreated();
        $response->assertJsonStructure(['data' => $this->jsonStructure]);
        $response->assertJsonPath('data.attributes.status', 'to_handle');
        $response->assertJsonPath('data.attributes.messages.0.attributes.attachments.0.name', 'invoice-october.pdf');
    }

    #[Test]
    public function it_hands_back_the_markdown_of_a_request_exactly_as_it_was_written(): void
    {
        Queue::fake();
        Notification::fake();
        Administrator::factory()->create(['user_id' => User::factory()->create()->id]);
        $body = "## Steps\n\n1. Open the invoice\n2. Read <b>Dunder Mifflin</b>, where a < b";

        Sanctum::actingAs(User::factory()->create());

        $response = $this->json('POST', '/api/v1/support-requests', [
            'category' => 'billing',
            'subject' => 'October invoice shows the wrong company name',
            'body' => $body,
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.attributes.messages.0.attributes.body', $body);
    }

    #[Test]
    public function it_refuses_a_sixth_request_within_a_minute(): void
    {
        Queue::fake();
        Notification::fake();
        Administrator::factory()->create(['user_id' => User::factory()->create()->id]);
        Sanctum::actingAs(User::factory()->create());

        $payload = ['category' => 'question', 'subject' => 'Printer', 'body' => 'The printer in the annex is on fire again.'];

        foreach (range(1, 5) as $attempt) {
            $this->json('POST', '/api/v1/support-requests', $payload)->assertCreated();
        }

        $this->json('POST', '/api/v1/support-requests', $payload)->assertTooManyRequests();
    }

    #[Test]
    public function it_refuses_a_request_too_short_to_start_from(): void
    {
        Administrator::factory()->create(['user_id' => User::factory()->create()->id]);
        Sanctum::actingAs(User::factory()->create());

        $response = $this->json('POST', '/api/v1/support-requests', ['category' => 'question', 'subject' => 'Help', 'body' => 'Help']);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['body']);
    }

    #[Test]
    public function it_hands_back_a_file_of_the_thread(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $supportRequest = SupportRequest::factory()->create(['account_id' => $user->account_id]);
        $attachment = SupportAttachment::factory()->create([
            'support_message_id' => SupportMessage::factory()->create(['support_request_id' => $supportRequest->id])->id,
            'disk' => 'local',
            'original_name' => 'reminder-email.png',
        ]);
        Storage::disk('local')->put($attachment->path, 'png');

        Sanctum::actingAs($user);

        $this->get("/api/v1/support-requests/{$supportRequest->uuid}/attachments/{$attachment->uuid}/content")
            ->assertDownload('reminder-email.png');
    }
}
