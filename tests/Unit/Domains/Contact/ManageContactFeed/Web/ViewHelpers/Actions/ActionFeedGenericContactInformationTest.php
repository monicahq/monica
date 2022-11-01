<?php

namespace Tests\Unit\Domains\Contact\ManageContactFeed\Web\ViewHelpers\Actions;

use App\Domains\Contact\ManageContactFeed\Web\ViewHelpers\Actions\ActionFeedGenericContactInformation;
use App\Models\Contact;
use App\Models\ContactFeedItem;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ActionFeedGenericContactInformationTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_gets_the_data_needed_for_the_view(): void
    {
        $contact = Contact::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);
        $feedItem = ContactFeedItem::factory()->create([
            'contact_id' => $contact->id,
        ]);

        $array = ActionFeedGenericContactInformation::data($feedItem);

        $this->assertEquals(
            [
                'id' => $contact->id,
                'name' => 'John Doe',
                'age' => null,
                'avatar' => $contact->avatar,
                'url' => env('APP_URL').'/vaults/'.$contact->vault->id.'/contacts/'.$contact->id,
            ],
            $array
        );
    }
}
