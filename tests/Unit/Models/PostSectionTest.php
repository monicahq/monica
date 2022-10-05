<?php

namespace Tests\Unit\Models;

use App\Models\PostSection;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PostSectionTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_has_one_post()
    {
        $postSection = PostSection::factory()->create();

        $this->assertTrue($postSection->post()->exists());
    }
}
