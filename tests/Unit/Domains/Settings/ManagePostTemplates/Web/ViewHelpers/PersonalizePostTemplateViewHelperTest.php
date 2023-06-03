<?php

namespace Tests\Unit\Domains\Settings\ManagePostTemplates\Web\ViewHelpers;

use App\Domains\Settings\ManagePostTemplates\Web\ViewHelpers\PersonalizePostTemplateViewHelper;
use App\Models\PostTemplate;
use App\Models\PostTemplateSection;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

use function env;

class PersonalizePostTemplateViewHelperTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_gets_the_data_needed_for_the_view(): void
    {
        $postTemplate = PostTemplate::factory()->create();
        $array = PersonalizePostTemplateViewHelper::data($postTemplate->account);
        $this->assertCount(
            2,
            $array
        );
        $this->assertArrayHasKey('post_templates', $array);
        $this->assertEquals(
            [
                'settings' => env('APP_URL').'/settings',
                'personalize' => env('APP_URL').'/settings/personalize',
                'store' => env('APP_URL').'/settings/personalize/postTemplates',
            ],
            $array['url']
        );
    }

    /** @test */
    public function it_gets_the_data_needed_for_the_data_transfer_object(): void
    {
        $postTemplate = PostTemplate::factory()->create();
        $postTemplateSection = PostTemplateSection::factory()->create([
            'post_template_id' => $postTemplate->id,
            'can_be_deleted' => false,
        ]);
        $array = PersonalizePostTemplateViewHelper::dto($postTemplate);
        $this->assertCount(6, $array);
        $this->assertEquals(
            $postTemplate->id,
            $array['id']
        );
        $this->assertEquals(
            $postTemplate->label,
            $array['label']
        );
        $this->assertEquals(
            $postTemplate->position,
            $array['position']
        );
        $this->assertFalse($array['can_be_deleted']);
        $this->assertEquals(
            [
                'store' => env('APP_URL').'/settings/personalize/postTemplates/'.$postTemplate->id.'/sections',
                'position' => env('APP_URL').'/settings/personalize/postTemplates/'.$postTemplate->id.'/position',
                'update' => env('APP_URL').'/settings/personalize/postTemplates/'.$postTemplate->id,
                'destroy' => env('APP_URL').'/settings/personalize/postTemplates/'.$postTemplate->id,
            ],
            $array['url']
        );
        $this->assertEquals(
            [
                'id' => $postTemplateSection->id,
                'label' => $postTemplateSection->label,
                'position' => $postTemplateSection->position,
                'can_be_deleted' => false,
                'post_template_id' => $postTemplate->id,
                'url' => [
                    'position' => env('APP_URL').'/settings/personalize/postTemplates/'.$postTemplate->id.'/sections/'.$postTemplateSection->id.'/position',
                    'update' => env('APP_URL').'/settings/personalize/postTemplates/'.$postTemplate->id.'/sections/'.$postTemplateSection->id,
                    'destroy' => env('APP_URL').'/settings/personalize/postTemplates/'.$postTemplate->id.'/sections/'.$postTemplateSection->id,
                ],
            ],
            $array['post_template_sections']->toArray()[0]
        );
    }
}
