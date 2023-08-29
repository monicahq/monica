<?php

namespace Tests\Unit\Domains\Settings\ManageTemplates\Web\ViewHelpers;

use App\Domains\Settings\ManageTemplates\Web\ViewHelpers\PersonalizeTemplateShowViewHelper;
use App\Models\Template;
use App\Models\TemplatePage;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

use function env;

class PersonalizeTemplateShowViewHelperTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_gets_the_data_needed_for_the_view(): void
    {
        $template = Template::factory()->create();
        $templatePage = TemplatePage::factory()->create([
            'template_id' => $template->id,
            'type' => 'contact_information',
        ]);
        $array = PersonalizeTemplateShowViewHelper::data($template);
        $this->assertEquals(
            4,
            count($array)
        );
        $this->assertArrayHasKey('template', $array);
        $this->assertArrayHasKey('template_pages', $array);
        $this->assertArrayHasKey('template_page_contact_information', $array);
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
                'can_be_deleted' => $templatePage->can_be_deleted,
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
