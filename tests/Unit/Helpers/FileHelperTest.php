<?php

namespace Tests\Unit\Helpers;

use App\Helpers\FileHelper;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class FileHelperTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_returns_the_file_size_in_the_right_format(): void
    {
        $this->assertEquals(
            '12.61MB',
            FileHelper::formatFileSize(13223239)
        );
        $this->assertEquals(
            '12.93kB',
            FileHelper::formatFileSize(13240)
        );
    }
}
