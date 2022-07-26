<?php

namespace Tests\Unit\Models;

use App\Models\File;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class FileTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_has_one_contact()
    {
        $file = File::factory()->create();

        $this->assertTrue($file->contact()->exists());
    }
}
