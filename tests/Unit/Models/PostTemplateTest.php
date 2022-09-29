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
}
