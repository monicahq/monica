<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\OAuthConnection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OAuthConnectionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_keeps_only_the_connections_still_running_as_active(): void
    {
        $running = OAuthConnection::factory()->create();
        OAuthConnection::factory()->ended()->create();
        OAuthConnection::factory()->create(['last_used_at' => now()->subDays(31)]);

        $this->assertSame([$running->id], OAuthConnection::query()->active()->pluck('id')->all());
    }

    #[Test]
    public function it_prunes_the_connections_nobody_can_use_again(): void
    {
        OAuthConnection::factory()->create();
        $ended = OAuthConnection::factory()->ended()->create();
        $unused = OAuthConnection::factory()->create(['last_used_at' => now()->subDays(31)]);

        $this->assertEqualsCanonicalizing(
            [$ended->id, $unused->id],
            new OAuthConnection()->prunable()->pluck('id')->all(),
        );
    }

    #[Test]
    public function it_is_named_after_its_label_or_else_its_client(): void
    {
        $labelled = OAuthConnection::factory()->make(['label' => 'Pixel 9']);
        $unlabelled = OAuthConnection::factory()->make(['label' => null]);

        $this->assertSame('Pixel 9', $labelled->name);
        $this->assertSame('Monica for mobile', $unlabelled->name);
    }
}
