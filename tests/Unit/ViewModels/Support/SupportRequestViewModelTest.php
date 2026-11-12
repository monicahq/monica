<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Support;

use App\Models\SupportAttachment;
use App\Models\SupportMessage;
use App\Models\SupportRequest;
use App\Models\User;
use App\ViewModels\Support\SupportRequestViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SupportRequestViewModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_shows_the_thread_without_the_internal_notes(): void
    {
        $user = User::factory()->create();
        $supportRequest = SupportRequest::factory()->create(['account_id' => $user->account_id]);
        $question = SupportMessage::factory()->create(['support_request_id' => $supportRequest->id, 'author_name' => 'Michael Scott']);
        SupportAttachment::factory()->create(['support_message_id' => $question->id, 'original_name' => 'reminder-email.png', 'size_in_bytes' => 245760]);
        SupportMessage::factory()->note()->create(['support_request_id' => $supportRequest->id]);
        SupportMessage::factory()->reply()->create(['support_request_id' => $supportRequest->id, 'author_name' => 'Jim Halpert']);

        $messages = new SupportRequestViewModel(user: $user, supportRequest: $supportRequest)->messages();

        $this->assertEquals(['Michael Scott', 'Jim Halpert'], array_column($messages, 'author'));
        $this->assertEquals([false, true], array_column($messages, 'isSupportTeam'));
        $this->assertEquals('reminder-email.png', $messages[0]['files'][0]['name']);
    }
}
