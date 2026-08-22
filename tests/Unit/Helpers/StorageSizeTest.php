<?php

declare(strict_types=1);

namespace Tests\Unit\Helpers;

use App\Helpers\StorageSize;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StorageSizeTest extends TestCase
{
    #[Test]
    public function it_leaves_bytes_as_bytes(): void
    {
        $this->assertEquals('0 B', StorageSize::readable(0));
        $this->assertEquals('512 B', StorageSize::readable(512));
        $this->assertEquals('1023 B', StorageSize::readable(1023));
    }

    #[Test]
    public function it_steps_up_a_unit_at_a_time(): void
    {
        $this->assertEquals('1 KB', StorageSize::readable(1024));
        $this->assertEquals('1 MB', StorageSize::readable(1024 * 1024));
        $this->assertEquals('1 GB', StorageSize::readable(1024 * 1024 * 1024));
        $this->assertEquals('1 TB', StorageSize::readable(1024 ** 4));
    }

    #[Test]
    public function it_keeps_one_decimal_once_it_is_past_bytes(): void
    {
        $this->assertEquals('1.5 KB', StorageSize::readable(1536));
        $this->assertEquals('2.5 MB', StorageSize::readable((int) (2.5 * 1024 * 1024)));
    }

    #[Test]
    public function it_stops_at_terabytes_rather_than_inventing_a_unit(): void
    {
        $this->assertEquals('2048 TB', StorageSize::readable(2048 * (1024 ** 4)));
    }
}
