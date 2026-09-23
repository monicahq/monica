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
}
