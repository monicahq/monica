<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\NotificationPreference;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NotificationPreferenceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_knows_whether_everything_is_folded_into_a_summary(): void
    {
        $immediate = NotificationPreference::factory()->create();
        $digest = NotificationPreference::factory()->digestAt()->create();

        $this->assertFalse($immediate->foldsIntoADigest());
        $this->assertTrue($digest->foldsIntoADigest());
    }

    #[Test]
    public function it_reads_a_stored_time_as_hours_and_minutes(): void
    {
        $preference = new NotificationPreference;
        $preference->setRawAttributes([
            'quiet_hours_start' => '22:00:00',
            'quiet_hours_end' => '08:30:00',
            'digest_time' => null,
        ]);

        $this->assertSame('22:00', $preference->quiet_hours_start);
        $this->assertSame('08:30', $preference->quiet_hours_end);
        $this->assertNull($preference->digest_time);
    }
}
