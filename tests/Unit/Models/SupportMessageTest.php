<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\SupportMessage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SupportMessageTest extends TestCase
{
    #[Test]
    public function it_tells_a_message_of_the_user_from_one_of_the_support_team(): void
    {
        $this->assertFalse(SupportMessage::factory()->make(['support_request_id' => 1])->isFromSupportTeam());
        $this->assertTrue(SupportMessage::factory()->reply()->make(['support_request_id' => 1])->isFromSupportTeam());
        $this->assertTrue(SupportMessage::factory()->note()->make(['support_request_id' => 1])->isFromSupportTeam());
    }
}
