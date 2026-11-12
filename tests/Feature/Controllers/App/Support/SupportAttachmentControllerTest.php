<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Support;

use App\Models\SupportAttachment;
use App\Models\SupportMessage;
use App\Models\SupportRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SupportAttachmentControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_hands_back_a_file_the_user_sent(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $message = SupportMessage::factory()->create([
            'support_request_id' => SupportRequest::factory()->create(['account_id' => $user->account_id])->id,
        ]);
        $attachment = SupportAttachment::factory()->create(['support_message_id' => $message->id, 'disk' => 'local', 'original_name' => 'invoice-october.pdf']);
        Storage::disk('local')->put($attachment->path, '%PDF');

        $response = $this->actingAs($user)->get(route('support.attachments.show', $attachment));

        $response->assertStatus(200);
        $response->assertDownload('invoice-october.pdf');
    }
}
