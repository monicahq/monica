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

    /** @test */
    public function it_gets_the_default_name()
    {
        $postTemplateSection = PostTemplateSection::factory()->create([
            'label' => null,
            'label_translation_key' => 'template.label',
        ]);

        $this->assertEquals(
            'template.label',
            $postTemplateSection->label
        );
    }

    /** @test */
    public function it_gets_the_custom_name_if_defined()
    {
        $postTemplateSection = PostTemplateSection::factory()->create([
            'label' => 'this is the real label',
            'label_translation_key' => 'template.label',
        ]);

        $this->assertEquals(
            'this is the real label',
            $postTemplateSection->label
        );
    }
}
