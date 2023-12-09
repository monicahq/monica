<?php

namespace Tests\Unit\Domains\Settings\ManageTemplates\Web\ViewHelpers;

use App\Domains\Settings\ManageTemplates\Web\ViewHelpers\PersonalizeTemplateIndexViewHelper;
use App\Models\Template;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

use function env;

class PersonalizeTemplateIndexViewHelperTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_gets_the_data_needed_for_the_view(): void
    {
        $template = Template::factory()->create();
        $array = PersonalizeTemplateIndexViewHelper::data($template->account);
        $this->assertEquals(
            2,
            count($array)
        );
        $this->assertArrayHasKey('templates', $array);
        $this->assertEquals(
            [
                'settings' => env('APP_URL').'/settings',
                'personalize' => env('APP_URL').'/settings/personalize',
                'template_store' => env('APP_URL').'/settings/personalize/templates',
            ],
            $array['url']
        );
    }

    /** @test */
    public function it_gets_the_data_needed_for_the_data_transfer_object(): void
    {
        $template = Template::factory()->create();
        $array = PersonalizeTemplateIndexViewHelper::dtoTemplate($template);
        $this->assertEquals(
            [
                'id' => $template->id,
                'name' => $template->name,
                'can_be_deleted' => $template->can_be_deleted,
                'url' => [
                    'show' => env('APP_URL').'/settings/personalize/templates/'.$template->id,
                    'update' => env('APP_URL').'/settings/personalize/templates/'.$template->id,
                    'destroy' => env('APP_URL').'/settings/personalize/templates/'.$template->id,
                ],
            ],
            $array
        );
    }
}
