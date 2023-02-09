<?php

namespace Tests\Unit\Domains\Vault\ManageReports\Web\ViewHelpers;

use App\Domains\Vault\ManageReports\Web\ViewHelpers\ReportCountriesShowViewHelper;
use App\Helpers\ContactCardHelper;
use App\Helpers\MapHelper;
use App\Models\Address;
use App\Models\Contact;
use App\Models\Vault;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ReportCountriesShowViewHelperTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_gets_the_data_needed_for_the_view(): void
    {
        $vault = Vault::factory()->create();
        $contact = Contact::factory()->create([
            'vault_id' => $vault->id,
        ]);
        $address = Address::factory()->create([
            'vault_id' => $vault->id,
            'city' => 'montreal',
            'country' => 'canada',
        ]);
        $contact->addresses()->attach($address);

        $array = ReportCountriesShowViewHelper::data($vault, 'canada');

        $this->assertCount(4, $array);
        $this->assertArrayHasKey('country', $array);
        $this->assertArrayHasKey('addresses', $array);
        $this->assertArrayHasKey('url', $array);
        $this->assertArrayHasKey('wikipedia', $array);

        $this->assertEquals(
            'Canada',
            $array['country']
        );
        $this->assertEquals(
            $address->id,
            $array['addresses']->toArray()[0]['id']
        );
        $this->assertEquals(
            'Canada',
            $array['addresses']->toArray()[0]['name']
        );
        $this->assertEquals(
            MapHelper::getAddressAsString($address),
            $array['addresses']->toArray()[0]['address']
        );
        $this->assertEquals(
            [
                0 => ContactCardHelper::data($contact),
            ],
            $array['addresses']->toArray()[0]['contacts']->toArray()
        );
        $this->assertEquals(
            [
                'addresses' => env('APP_URL').'/vaults/'.$vault->id.'/reports/addresses',
            ],
            $array['url']
        );
    }
}
