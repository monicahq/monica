<?php

namespace Tests\Unit\Domains\Settings\ManageAddressTypes\Web\ViewHelpers;

use App\Domains\Settings\ManageAddressTypes\Web\ViewHelpers\PersonalizeAddressTypeIndexViewHelper;
use App\Models\AddressType;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

use function env;

class PersonalizeAddressTypeIndexViewHelperTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_gets_the_data_needed_for_the_view(): void
    {
        $addressType = AddressType::factory()->create();
        $array = PersonalizeAddressTypeIndexViewHelper::data($addressType->account);
        $this->assertEquals(
            2,
            count($array)
        );
        $this->assertArrayHasKey('address_types', $array);
        $this->assertEquals(
            [
                'settings' => env('APP_URL').'/settings',
                'personalize' => env('APP_URL').'/settings/personalize',
                'address_type_store' => env('APP_URL').'/settings/personalize/addressTypes',
            ],
            $array['url']
        );
    }

    /** @test */
    public function it_gets_the_data_needed_for_the_data_transfer_object(): void
    {
        $addressType = AddressType::factory()->create();
        $array = PersonalizeAddressTypeIndexViewHelper::dtoAddressType($addressType);
        $this->assertEquals(
            [
                'id' => $addressType->id,
                'name' => $addressType->name,
                'url' => [
                    'update' => env('APP_URL').'/settings/personalize/addressTypes/'.$addressType->id,
                    'destroy' => env('APP_URL').'/settings/personalize/addressTypes/'.$addressType->id,
                ],
            ],
            $array
        );
    }
}
