<?php

namespace Tests\Unit\Domains\Contact\ManageContactAddresses\Web\ViewHelpers;

use App\Contact\ManageContactAddresses\Web\ViewHelpers\ModuleContactAddressesViewHelper;
use App\Models\Address;
use App\Models\AddressType;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use function env;

class ModuleContactAddressesViewHelperTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_gets_the_data_needed_for_the_view(): void
    {
        $contact = Contact::factory()->create();
        $user = User::factory()->create();

        Address::factory()->create([
            'contact_id' => $contact->id,
            'is_past_address' => false,
        ]);
        Address::factory()->create([
            'contact_id' => $contact->id,
            'is_past_address' => true,
        ]);

        $addressType = AddressType::factory()->create([
            'account_id' => $contact->vault->account_id,
            'name' => 'super type',
        ]);

        $array = ModuleContactAddressesViewHelper::data($contact, $user);

        $this->assertEquals(
            4,
            count($array)
        );

        $this->assertArrayHasKey('active_addresses', $array);
        $this->assertArrayHasKey('inactive_addresses', $array);
        $this->assertArrayHasKey('address_types', $array);
        $this->assertArrayHasKey('url', $array);

        $this->assertEquals(
            [
                0 => [
                    'id' => $addressType->id,
                    'name' => 'super type',
                    'selected' => false,
                ],
            ],
            $array['address_types']->toArray()
        );

        $this->assertEquals(
            1,
            $array['active_addresses']->count()
        );

        $this->assertEquals(
            1,
            $array['inactive_addresses']->count()
        );

        $this->assertEquals(
            [
                'store' => env('APP_URL').'/vaults/'.$contact->vault->id.'/contacts/'.$contact->id.'/addresses',
            ],
            $array['url']
        );
    }

    /** @test */
    public function it_gets_the_data_transfer_object(): void
    {
        $contact = Contact::factory()->create();
        $user = User::factory()->create();

        $addressType = AddressType::factory()->create([
            'account_id' => $contact->vault->account_id,
            'name' => 'super type',
        ]);

        $activeAddress = Address::factory()->create([
            'street' => '123 main st',
            'city' => 'montreal',
            'province' => 'quebec',
            'postal_code' => 'h1k 12k',
            'country' => 'Canada',
            'is_past_address' => false,
            'address_type_id' => $addressType->id,
        ]);

        $collection = ModuleContactAddressesViewHelper::dto($contact, $activeAddress, $user);

        $this->assertEquals(
            [
                'id' => $activeAddress->id,
                'is_past_address' => false,
                'street' => $activeAddress->street,
                'city' => $activeAddress->city,
                'province' => $activeAddress->province,
                'postal_code' => $activeAddress->postal_code,
                'country' => $activeAddress->country,
                'type' => [
                    'id' => $addressType->id,
                    'name' => 'super type',
                ],
                'url' => [
                    'show' => 'https://www.google.com/maps/place/123+main+st+montreal+quebec+h1k+12k+Canada',
                    'update' => env('APP_URL').'/vaults/'.$contact->vault->id.'/contacts/'.$contact->id.'/addresses/'.$activeAddress->id,
                    'destroy' => env('APP_URL').'/vaults/'.$contact->vault->id.'/contacts/'.$contact->id.'/addresses/'.$activeAddress->id,
                ],
            ],
            $collection
        );
    }
}
