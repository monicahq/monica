<?php

namespace Tests\Unit\Models;

use App\Models\PostTemplateSection;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PostTemplateSectionTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_has_one_post_template()
    {
        $postTemplateSection = PostTemplateSection::factory()->create();

        $this->assertTrue($postTemplateSection->postTemplate()->exists());
    }
}
