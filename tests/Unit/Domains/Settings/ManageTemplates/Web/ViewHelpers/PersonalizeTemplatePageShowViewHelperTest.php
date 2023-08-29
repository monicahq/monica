<?php

namespace Tests\Unit\Domains\Settings\ManageTemplates\Web\ViewHelpers;

use App\Domains\Settings\ManageTemplates\Web\ViewHelpers\PersonalizeTemplatePageShowViewHelper;
use App\Models\Module;
use App\Models\Template;
use App\Models\TemplatePage;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

use function env;

class PersonalizeTemplatePageShowViewHelperTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_gets_the_data_needed_for_the_view(): void
    {
        $template = Template::factory()->create();
        $module = Module::factory()->create([
            'account_id' => $template->account->id,
        ]);
        $templatePage = TemplatePage::factory()->create([
            'template_id' => $template->id,
        ]);
        $templatePage->modules()->syncWithoutDetaching($module);
        $array = PersonalizeTemplatePageShowViewHelper::data($templatePage);
        $this->assertEquals(
            4,
            count($array)
        );
        $this->assertArrayHasKey('page', $array);
        $this->assertArrayHasKey('modules', $array);
        $this->assertArrayHasKey('modules_in_account', $array);
        $this->assertArrayHasKey('url', $array);

        $this->assertEquals(
            [
                'id' => $templatePage->id,
                'name' => $templatePage->name,
            ],
            $array['page']
        );

        $this->assertEquals(
            [
                0 => [
                    'id' => $module->id,
                    'name' => $module->name,
                    'already_used' => true,
                    'url' => [
                        'destroy' => env('APP_URL').'/settings/personalize/templates/'.$template->id.'/template_pages/'.$templatePage->id.'/modules/'.$module->id,
                    ],
                ],
            ],
            $array['modules_in_account']->toArray()
        );

        $this->assertEquals(
            [
                0 => [
                    'id' => $module->id,
                    'name' => $module->name,
                    'position' => $module->position,
                    'url' => [
                        'position' => env('APP_URL').'/settings/personalize/templates/'.$template->id.'/template_pages/'.$templatePage->id.'/modules/'.$module->id.'/order',
                        'destroy' => env('APP_URL').'/settings/personalize/templates/'.$template->id.'/template_pages/'.$templatePage->id.'/modules/'.$module->id,
                    ],
                ],
            ],
            $array['modules']->toArray()
        );

        $this->assertEquals(
            [
                'store' => env('APP_URL').'/settings/personalize/templates/'.$template->id.'/template_pages/'.$templatePage->id.'/modules',
            ],
            $array['url']
        );
    }

    /** @test */
    public function it_gets_the_data_needed_for_the_data_transfer_object(): void
    {
        $template = Template::factory()->create();
        $module = Module::factory()->create([
            'account_id' => $template->account->id,
        ]);
        $templatePage = TemplatePage::factory()->create([
            'template_id' => $template->id,
        ]);
        $templatePage->modules()->syncWithoutDetaching($module);

        $array = PersonalizeTemplatePageShowViewHelper::dtoModule($templatePage, $module);
        $this->assertEquals(
            [
                'id' => $module->id,
                'name' => $module->name,
                'position' => $module->position,
                'url' => [
                    'position' => env('APP_URL').'/settings/personalize/templates/'.$template->id.'/template_pages/'.$templatePage->id.'/modules/'.$module->id.'/order',
                    'destroy' => env('APP_URL').'/settings/personalize/templates/'.$template->id.'/template_pages/'.$templatePage->id.'/modules/'.$module->id,
                ],
            ],
            $array
        );
    }
}
