<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\SupportMessage;
use App\Models\SupportRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SupportRequestTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_is_quoted_by_its_number(): void
    {
        $request = SupportRequest::factory()->make(['number' => 482913]);

        $this->assertEquals('MON-482913', $request->reference);
    }

    #[Test]
    public function it_leaves_the_internal_notes_out_of_what_the_user_may_read(): void
    {
        $request = SupportRequest::factory()->create();
        $question = SupportMessage::factory()->create(['support_request_id' => $request->id, 'body' => 'Why does my invoice say Dunder Mifflin?']);
        $reply = SupportMessage::factory()->reply()->create(['support_request_id' => $request->id, 'body' => 'We changed it to Michael Scott Paper Company.']);
        SupportMessage::factory()->note()->create(['support_request_id' => $request->id, 'body' => 'Toby says this happens every year.']);

        $this->assertEquals(
            [$question->id, $reply->id],
            $request->visibleMessages()->pluck('id')->all(),
        );
        $this->assertCount(3, $request->messages);
    }
}
