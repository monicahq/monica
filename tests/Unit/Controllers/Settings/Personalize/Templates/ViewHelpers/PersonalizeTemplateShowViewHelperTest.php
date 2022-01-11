<?php

namespace Tests\Unit\Controllers\Settings\Personalize\Templates\ViewHelpers;

use function env;
use Tests\TestCase;
use App\Models\Template;
use App\Models\TemplatePage;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Http\Controllers\Settings\Personalize\Templates\ViewHelpers\PersonalizeTemplateShowViewHelper;

class PersonalizeTemplateShowViewHelperTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_gets_the_data_needed_for_the_view(): void
    {
        $template = Template::factory()->create();
        $templatePage = TemplatePage::factory()->create([
            'template_id' => $template->id,
        ]);
        $array = PersonalizeTemplateShowViewHelper::data($template);
        $this->assertEquals(
            3,
            count($array)
        );
        $this->assertArrayHasKey('template', $array);
        $this->assertArrayHasKey('template_pages', $array);
        $this->assertArrayHasKey('url', $array);

        $this->assertEquals(
            [
                'id' => $template->id,
                'name' => $template->name,
            ],
            $array['template']
        );

        $this->assertEquals(
            [
                'settings' => env('APP_URL').'/settings',
                'personalize' => env('APP_URL').'/settings/personalize',
                'templates' => env('APP_URL').'/settings/personalize/templates',
                'template_page_store' => env('APP_URL').'/settings/personalize/templates/'.$template->id,
            ],
            $array['url']
        );
    }

    /** @test */
    public function it_gets_the_data_needed_for_the_data_transfer_object(): void
    {
        $template = Template::factory()->create();
        $templatePage = TemplatePage::factory()->create([
            'template_id' => $template->id,
        ]);

        $array = PersonalizeTemplateShowViewHelper::dtoTemplatePage($template, $templatePage);
        $this->assertEquals(
            [
                'id' => $templatePage->id,
                'name' => $templatePage->name,
                'position' => $templatePage->position,
                'url' => [
                    'show' => env('APP_URL').'/settings/personalize/templates/'.$template->id.'/template_pages/'.$templatePage->id,
                    'update' => env('APP_URL').'/settings/personalize/templates/'.$template->id.'/template_pages/'.$templatePage->id,
                    'order' => env('APP_URL').'/settings/personalize/templates/'.$template->id.'/template_pages/'.$templatePage->id.'/order',
                    'destroy' => env('APP_URL').'/settings/personalize/templates/'.$template->id.'/template_pages/'.$templatePage->id,
                ],
            ],
            $array
        );
    }
}
