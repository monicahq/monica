<?php

namespace Tests\Unit\Domains\Contact\ManageContact\Web\ViewHelpers;

use App\Contact\ManageContact\Web\ViewHelpers\ContactShowViewHelper;
use App\Models\Contact;
use App\Models\Module;
use App\Models\Template;
use App\Models\TemplatePage;
use App\Models\User;
use function env;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ContactShowViewHelperTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_gets_the_data_needed_for_the_view(): void
    {
        $contact = Contact::factory()->create();
        $user = User::factory()->create();
        $template = Template::factory()->create();
        TemplatePage::factory()->create([
            'template_id' => $template->id,
            'type' => TemplatePage::TYPE_CONTACT,
            'position' => 1,
        ]);
        $templatePage = TemplatePage::factory()->create([
            'template_id' => $template->id,
            'position' => 2,
        ]);
        $contact->template_id = $template->id;
        $contact->save();

        $array = ContactShowViewHelper::data($contact, $user);

        $this->assertEquals(
            5,
            count($array)
        );

        $this->assertArrayHasKey('contact_name', $array);
        $this->assertArrayHasKey('template_pages', $array);
        $this->assertArrayHasKey('contact_information', $array);
        $this->assertArrayHasKey('modules', $array);
        $this->assertArrayHasKey('url', $array);

        $this->assertEquals(
            [
                0 => [
                    'id' => $templatePage->id,
                    'name' => $templatePage->name,
                    'selected' => true,
                    'url' => [
                        'show' => env('APP_URL').'/vaults/'.$contact->vault->id.'/contacts/'.$contact->id.'/tabs/'.$templatePage->slug,
                    ],
                ],
            ],
            $array['template_pages']->toArray()
        );
        $this->assertEquals(
            [
                'destroy' => env('APP_URL').'/vaults/'.$contact->vault->id.'/contacts/'.$contact->id,
            ],
            $array['url']
        );
    }

    /** @test */
    public function it_gets_the_modules_in_a_given_page(): void
    {
        $contact = Contact::factory()->create();
        $user = User::factory()->create();
        $templatePage = TemplatePage::factory()->create([
            'position' => 2,
        ]);
        $module = Module::factory()->create([
            'type' => Module::TYPE_NOTES,
        ]);
        $templatePage->modules()->syncWithoutDetaching($module);

        $collection = ContactShowViewHelper::modules($templatePage, $contact, $user);

        $this->assertEquals(
            1,
            $collection->count()
        );

        $this->assertEquals(
            $module->id,
            $collection[0]['id']
        );
        $this->assertEquals(
            $module->type,
            $collection[0]['type']
        );
    }
}
