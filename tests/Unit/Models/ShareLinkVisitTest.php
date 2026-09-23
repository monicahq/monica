<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\ShareLinkVisit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ShareLinkVisitTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_keeps_the_account_of_the_link_it_records(): void
    {
        $visit = ShareLinkVisit::factory()->create();

        $this->assertEquals($visit->shareLink->account_id, $visit->account_id);
    }

    #[Test]
    public function it_records_an_opening_that_needed_no_password(): void
    {
        $visit = ShareLinkVisit::factory()->create();

        $this->assertFalse($visit->password_was_required);
        $this->assertFalse($visit->password_failed);
        $this->assertNotNull($visit->viewed_at);
    }

    #[Test]
    public function it_records_an_attempt_that_failed_on_the_password(): void
    {
        $visit = ShareLinkVisit::factory()->passwordFailed()->create();

        $this->assertTrue($visit->password_was_required);
        $this->assertTrue($visit->password_failed);
    }
}
