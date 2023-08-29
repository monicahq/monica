<?php

namespace Tests\Unit\Domains\Contact\ManageContact\Web\ViewHelpers;

use App\Domains\Contact\ManageContact\Web\ViewHelpers\ContactShowViewHelper;
use App\Models\Contact;
use App\Models\Module;
use App\Models\Template;
use App\Models\TemplatePage;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

use function env;

class ContactShowViewHelperTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_gets_the_data_needed_for_the_view(): void
    {
        $contact = Contact::factory()->create();
        $user = User::factory()->create();
        $vault = Vault::factory()->create([
            'account_id' => $user->account_id,
        ]);
        $contact = Contact::factory()->create([
            'vault_id' => $vault->id,
        ]);
        $vault->users()->save($user, [
            'permission' => 1,
            'contact_id' => $contact->id,
        ]);
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
            10,
            count($array)
        );

        $this->assertArrayHasKey('contact_name', $array);
        $this->assertArrayHasKey('listed', $array);
        $this->assertArrayHasKey('template_pages', $array);
        $this->assertArrayHasKey('contact_information', $array);
        $this->assertArrayHasKey('group_summary_information', $array);
        $this->assertArrayHasKey('quick_fact_template_entries', $array);
        $this->assertArrayHasKey('modules', $array);
        $this->assertArrayHasKey('options', $array);
        $this->assertArrayHasKey('avatar', $array);
        $this->assertArrayHasKey('url', $array);

        $this->assertEquals(
            [
                1 => [
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
            $contact->listed,
            $array['listed']
        );
        $this->assertEquals(
            [
                'toggle_archive' => env('APP_URL').'/vaults/'.$contact->vault->id.'/contacts/'.$contact->id.'/toggle',
                'update_template' => env('APP_URL').'/vaults/'.$contact->vault->id.'/contacts/'.$contact->id.'/update-template',
                'destroy' => env('APP_URL').'/vaults/'.$contact->vault->id.'/contacts/'.$contact->id,
                'update_avatar' => env('APP_URL').'/vaults/'.$contact->vault->id.'/contacts/'.$contact->id.'/avatar',
                'move_contact' => env('APP_URL').'/vaults/'.$contact->vault->id.'/contacts/'.$contact->id.'/move',
                'destroy_avatar' => env('APP_URL').'/vaults/'.$contact->vault->id.'/contacts/'.$contact->id.'/avatar',
                'download_vcard' => env('APP_URL').'/vaults/'.$contact->vault->id.'/contacts/'.$contact->id.'/vcard',
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
