<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Admin;

use App\Models\Administrator;
use App\Models\SupportAttachment;
use App\Models\SupportMessage;
use App\Models\SupportRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SupportControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_shows_the_queue_with_the_oldest_request_open(): void
    {
        $michael = User::factory()->create(['first_name' => 'Michael', 'last_name' => 'Scott']);
        SupportRequest::factory()->create(['account_id' => $michael->account_id, 'user_id' => $michael->id, 'subject' => 'October invoice shows the wrong company name']);

        $this->actingAs($this->administrator())->get(route('admin.support.index'))
            ->assertStatus(200)
            ->assertSee('October invoice shows the wrong company name', escape: false)
            ->assertSee('Michael Scott', escape: false);
    }

    #[Test]
    public function it_opens_a_request_of_any_account(): void
    {
        $supportRequest = SupportRequest::factory()->create(['subject' => 'vCard import stops at 50']);

        $this->actingAs($this->administrator())->get(route('admin.support.show', ['supportRequest' => $supportRequest->uuid]))
            ->assertStatus(200)
            ->assertSee('vCard import stops at 50', escape: false);
    }

    #[Test]
    public function it_sends_a_reply_and_comes_back_to_the_request(): void
    {
        Queue::fake();
        Notification::fake();
        $supportRequest = SupportRequest::factory()->create();

        $response = $this->actingAs($this->administrator())->post(route('admin.support.replies.create', ['supportRequest' => $supportRequest->uuid]), [
            'body' => 'Could you send the file?',
            'status' => 'waiting_on_user',
        ]);

        $response->assertRedirect(route('admin.support.show', ['supportRequest' => $supportRequest->uuid]));
        $response->assertSessionHas('status', 'Your reply is sent.');
        $this->assertEquals('waiting_on_user', $supportRequest->refresh()->status->value);
    }

    #[Test]
    public function it_refuses_a_reply_without_a_status_to_move_to(): void
    {
        $supportRequest = SupportRequest::factory()->create();

        $this->actingAs($this->administrator())->post(route('admin.support.replies.create', ['supportRequest' => $supportRequest->uuid]), [
            'body' => 'Could you send the file?',
            'status' => 'Scranton',
        ])->assertSessionHasErrors(['status']);
    }

    #[Test]
    public function it_adds_a_note(): void
    {
        $supportRequest = SupportRequest::factory()->create();

        $this->actingAs($this->administrator())->post(route('admin.support.notes.create', ['supportRequest' => $supportRequest->uuid]), [
            'body' => 'Check the domain reputation first.',
        ])->assertSessionHas('status', 'Note added.');

        $this->assertDatabaseHas('support_messages', ['support_request_id' => $supportRequest->id, 'kind' => 'note']);
    }

    #[Test]
    public function it_keeps_a_refused_note_apart_from_the_reply(): void
    {
        $supportRequest = SupportRequest::factory()->create();

        $this->actingAs($this->administrator())
            ->from(route('admin.support.show', ['supportRequest' => $supportRequest->uuid]))
            ->followingRedirects()
            ->post(route('admin.support.notes.create', ['supportRequest' => $supportRequest->uuid]), ['body' => ''])
            ->assertSee('mode: \'note\'', escape: false);
    }

    #[Test]
    public function it_moves_a_request(): void
    {
        Queue::fake();
        $supportRequest = SupportRequest::factory()->create();

        $this->actingAs($this->administrator())->put(route('admin.support.status.update', ['supportRequest' => $supportRequest->uuid]), [
            'status' => 'solved',
        ])->assertSessionHas('status', 'Status changed.');

        $this->assertEquals('solved', $supportRequest->refresh()->status->value);
    }

    #[Test]
    public function it_hands_back_a_file_the_user_sent(): void
    {
        Storage::fake('local');
        $attachment = SupportAttachment::factory()->create([
            'support_message_id' => SupportMessage::factory()->create()->id,
            'disk' => 'local',
            'original_name' => 'reminder-email.png',
        ]);
        Storage::disk('local')->put($attachment->path, 'png');

        $this->actingAs($this->administrator())->get(route('admin.support.attachments.show', ['supportAttachment' => $attachment->uuid]))
            ->assertDownload('reminder-email.png');
    }

    private function administrator(): User
    {
        $user = User::factory()->create();
        Administrator::factory()->create(['user_id' => $user->id]);

        return $user;
    }
}
