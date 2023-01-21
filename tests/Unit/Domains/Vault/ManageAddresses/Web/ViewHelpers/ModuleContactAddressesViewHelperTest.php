<?php

namespace Tests\Unit\Domains\Vault\ManageAddresses\Web\ViewHelpers;

use App\Domains\Contact\ManageContactAddresses\Web\ViewHelpers\ModuleContactAddressesViewHelper;
use App\Models\Address;
use App\Models\AddressType;
use App\Models\Contact;
use App\Models\User;
use function env;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ModuleContactAddressesViewHelperTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_gets_the_data_needed_for_the_view(): void
    {
        $contact = Contact::factory()->create();
        $user = User::factory()->create();

        $address = Address::factory()->create();
        $address->contacts()->attach($contact, ['is_past_address' => true]);
        $address = Address::factory()->create();
        $address->contacts()->attach($contact, ['is_past_address' => false]);

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
            'line_1' => '123 main st',
            'line_2' => 'Apartment 4',
            'city' => 'montreal',
            'province' => 'quebec',
            'postal_code' => 'h1k 12k',
            'country' => 'Canada',
            'address_type_id' => $addressType->id,
        ]);

        $activeAddress->contacts()->attach($contact, ['is_past_address' => false]);
        $address = $contact->addresses()->where('address_id', $activeAddress->id)->first();

        $collection = ModuleContactAddressesViewHelper::dto($contact, $address, $user);

        $this->assertEquals(
            [
                'id' => $address->id,
                'is_past_address' => false,
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
                'url' => [
                    'show' => 'https://www.google.com/maps/place/123+main+st+Apartment+4+montreal+quebec+h1k+12k+Canada',
                    'update' => env('APP_URL').'/vaults/'.$contact->vault->id.'/contacts/'.$contact->id.'/addresses/'.$activeAddress->id,
                    'destroy' => env('APP_URL').'/vaults/'.$contact->vault->id.'/contacts/'.$contact->id.'/addresses/'.$activeAddress->id,
                ],
            ],
            $collection
        );
    }
}
