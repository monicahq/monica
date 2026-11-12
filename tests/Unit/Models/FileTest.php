<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\File;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FileTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_is_written_under_the_account_and_then_the_vault(): void
    {
        $this->assertEquals('accounts/12', File::directoryFor(12, null));
        $this->assertEquals('accounts/12/vaults/7', File::directoryFor(12, 7));
    }

    #[Test]
    public function it_is_an_orphan_when_nothing_points_at_it(): void
    {
        $orphan = File::factory()->create();
        $used = File::factory()->used()->create();

        $this->assertTrue($orphan->isOrphan());
        $this->assertFalse($used->isOrphan());
    }
}
