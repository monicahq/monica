<?php

namespace Tests\Unit\Domains\Contact\ManageContactFeed\Web\ViewHelpers\Actions;

use App\Domains\Contact\ManageContactFeed\Web\ViewHelpers\Actions\ActionFeedAddress;
use App\Models\Address;
use App\Models\AddressType;
use App\Models\Contact;
use App\Models\ContactFeedItem;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ActionFeedAddressTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_gets_the_data_needed_for_the_view(): void
    {
        $user = User::factory()->create();
        $contact = Contact::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);
        $addressType = AddressType::factory()->create([
            'account_id' => $contact->vault->account_id,
            'name' => 'super type',
        ]);

        $activeAddress = Address::factory()->create([
            'line_1' => '123 main st',
            'line_2' => 'Apartment 4',
            'city' => 'montreal',
            'province' => 'quebec',
            'postal_code' => 'h1k 12k',
            'country' => 'Canada',
            'address_type_id' => $addressType->id,
            'longitude' => '-74.005941',
            'latitude' => '40.712784',
        ]);

        config(['monica.mapbox_api_key' => 'api_key']);
        config(['monica.mapbox_username' => 'test']);

        $feedItem = ContactFeedItem::factory()->create([
            'contact_id' => $contact->id,
            'action' => ContactFeedItem::ACTION_CONTACT_ADDRESS_CREATED,
            'description' => 'test',
        ]);
        $activeAddress->feedItem()->save($feedItem);

        $array = ActionFeedAddress::data($feedItem, $user);

        $this->assertEquals(
            [
                'address' => [
                    'object' => [
                        'id' => $activeAddress->id,
                        'line_1' => '123 main st',
                        'line_2' => 'Apartment 4',
                        'city' => 'montreal',
                        'province' => 'quebec',
                        'postal_code' => 'h1k 12k',
                        'country' => 'Canada',
                        'type' => [
                            'id' => $addressType->id,
                            'name' => 'super type',
                        ],
                        'image' => env('APP_URL')."/vaults/{$contact->vault_id}/contacts/{$contact->id}/addresses/{$activeAddress->id}/image/300x100",
                        'url' => [
                            'show' => 'https://www.google.com/maps/place/123+main+st+Apartment+4+montreal+quebec+h1k+12k+Canada',
                        ],
                    ],
                    'description' => 'test',
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
