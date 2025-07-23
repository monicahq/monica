<?php

namespace Tests\Unit\Domains\Contact\ManageContactInformation\Web\ViewHelpers;

use App\Domains\Contact\ManageContactInformation\Web\ViewHelpers\ModuleContactInformationViewHelper;
use App\Models\Contact;
use App\Models\ContactInformation;
use App\Models\ContactInformationType;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ModuleContactInformationViewHelperTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_gets_the_data_needed_for_the_view(): void
    {
        $contact = Contact::factory()->create();
        $user = User::factory()->create();
        $type = ContactInformationType::factory()->create([
            'account_id' => $user->account_id,
            'name' => 'Facebook shit',
            'protocol' => 'mailto:',
        ]);
        ContactInformation::factory()->create([
            'contact_id' => $contact->id,
            'type_id' => $type->id,
        ]);

        $array = ModuleContactInformationViewHelper::data($contact, $user);

        $this->assertEquals(
            6,
            count($array)
        );

        $this->assertArrayHasKey('contact_information', $array);
        $this->assertArrayHasKey('contact_information_types', $array);
        $this->assertArrayHasKey('url', $array);

        $this->assertEquals(
            [
                'email' => [
                    'optgroup' => 'Email address',
                    'options' => [
                        0 => [
                            'id' => $type->id,
                            'name' => $type->name,
                            'type' => 'email',
                            'name_translation_key' => null,
                        ],
                    ],
                ],
            ],
            $array['contact_information_types']->toArray()
        );

        $this->assertEquals(
            [
                'store' => env('APP_URL').'/vaults/'.$contact->vault->id.'/contacts/'.$contact->id.'/contactInformation',
            ],
            $array['url']
        );
    }

    /** @test */
    public function it_gets_the_data_transfer_object(): void
    {
        $contact = Contact::factory()->create();
        $type = ContactInformationType::factory()->create([
            'name' => 'Facebook shit',
            'protocol' => 'mailto:',
            'can_be_deleted' => true,
        ]);
        $info = ContactInformation::factory()->create([
            'contact_id' => $contact->id,
            'type_id' => $type->id,
        ]);

        $array = ModuleContactInformationViewHelper::dto($info);

        $this->assertEquals(
            [
                'id' => $info->id,
                'label' => 'Facebook shit',
                'protocol' => 'mailto:',
                'data' => $info->data,
                'data_with_protocol' => 'mailto:'.$info->data,
                'contact_information_type' => [
                    'id' => $type->id,
                    'name' => 'Facebook shit',
                    'type' => 'email',
                ],
                'contact_information_kind' => null,
                'url' => [
                    'update' => env('APP_URL').'/vaults/'.$contact->vault->id.'/contacts/'.$contact->id.'/contactInformation/'.$info->id,
                    'destroy' => env('APP_URL').'/vaults/'.$contact->vault->id.'/contacts/'.$contact->id.'/contactInformation/'.$info->id,
                ],
            ],
            $array
        );
    }
}
