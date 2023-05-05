<?php

namespace Tests\Unit\Models;

use App\Models\PostTemplate;
use App\Models\PostTemplateSection;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PostTemplateTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_has_one_account()
    {
        $postTemplate = PostTemplate::factory()->create();

        $this->assertTrue($postTemplate->account()->exists());
    }

    /** @test */
    public function it_has_many_sections(): void
    {
        $postTemplate = PostTemplate::factory()->create();

        PostTemplateSection::factory()->create([
            'post_template_id' => $postTemplate->id,
        ]);

        $this->assertTrue($postTemplate->postTemplateSections()->exists());
    }

    /** @test */
    public function it_gets_the_default_label()
    {
        $postTemplate = PostTemplate::factory()->create([
            'label' => null,
            'label_translation_key' => 'template.label',
        ]);

        $this->assertEquals(
            'template.label',
            $postTemplate->label
        );
    }

    /** @test */
    public function it_gets_the_custom_label_if_defined()
    {
        $postTemplate = PostTemplate::factory()->create([
            'label' => 'this is the real name',
            'label_translation_key' => 'template.label',
        ]);

        $this->assertEquals(
            'this is the real name',
            $postTemplate->label
        );
    }
}
