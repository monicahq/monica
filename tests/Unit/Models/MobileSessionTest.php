<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\MobileSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MobileSessionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_keeps_only_the_sessions_still_running_as_active(): void
    {
        $running = MobileSession::factory()->create();
        MobileSession::factory()->ended()->create();
        MobileSession::factory()->create(['last_used_at' => now()->subDays(31)]);

        $this->assertSame([$running->id], MobileSession::query()->active()->pluck('id')->all());
    }

    #[Test]
    public function it_prunes_the_sessions_nobody_can_use_again(): void
    {
        MobileSession::factory()->create();
        $ended = MobileSession::factory()->ended()->create();
        $unused = MobileSession::factory()->create(['last_used_at' => now()->subDays(31)]);

        $this->assertEqualsCanonicalizing(
            [$ended->id, $unused->id],
            new MobileSession()->prunable()->pluck('id')->all(),
        );
    }
}
