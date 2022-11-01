<?php

namespace Tests\Unit\Domains\Contact\ManageContactFeed\Web\ViewHelpers\Actions;

use App\Domains\Contact\ManageContactFeed\Web\ViewHelpers\Actions\ActionFeedContactInformation;
use App\Models\Contact;
use App\Models\ContactFeedItem;
use App\Models\ContactInformation;
use App\Models\ContactInformationType;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ActionFeedContactInformationTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_gets_the_data_needed_for_the_view(): void
    {
        $contact = Contact::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);
        $type = ContactInformationType::factory()->create([
            'name' => 'Facebook shit',
            'protocol' => 'mailto:',
            'can_be_deleted' => true,
        ]);
        $info = ContactInformation::factory()->create([
            'contact_id' => $contact->id,
            'type_id' => $type->id,
        ]);

        $feedItem = ContactFeedItem::factory()->create([
            'contact_id' => $contact->id,
            'action' => ContactFeedItem::ACTION_CONTACT_INFORMATION_CREATED,
            'description' => 'label',
        ]);
        $info->feedItem()->save($feedItem);

        $array = ActionFeedContactInformation::data($feedItem);

        $this->assertEquals(
            [
                'information' => [
                    'object' => [
                        'id' => $info->id,
                        'label' => 'Facebook shit',
                        'data' => 'mailto:'.$info->data,
                        'contact_information_type' => [
                            'id' => $type->id,
                            'name' => 'Facebook shit',
                        ],
                    ],
                    'description' => 'label',
                ],
                'contact' => [
                    'id' => $contact->id,
                    'name' => 'John Doe',
                    'age' => null,
                    'avatar' => $contact->avatar,
                    'url' => env('APP_URL').'/vaults/'.$contact->vault_id.'/contacts/'.$contact->id,
                ],
            ],
            $array
        );
    }
}
