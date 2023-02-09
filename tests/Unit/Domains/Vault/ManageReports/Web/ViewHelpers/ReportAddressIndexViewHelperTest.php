<?php

namespace Tests\Unit\Domains\Vault\ManageReports\Web\ViewHelpers;

use App\Domains\Vault\ManageReports\Web\ViewHelpers\ReportAddressIndexViewHelper;
use App\Models\Address;
use App\Models\Contact;
use App\Models\Vault;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ReportAddressIndexViewHelperTest extends TestCase
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

        $array = ReportAddressIndexViewHelper::data($vault);

        $this->assertCount(2, $array);
        $this->assertArrayHasKey('cities', $array);
        $this->assertArrayHasKey('countries', $array);

        $this->assertEquals(
            [
                0 => [
                    'id' => $address->id,
                    'name' => 'Montreal',
                    'contacts' => 1,
                    'url' => [
                        'index' => env('APP_URL').'/vaults/'.$vault->id.'/reports/addresses/city/montreal',
                    ],
                ],
            ],
            $array['cities']->toArray()
        );
        $this->assertEquals(
            [
                0 => [
                    'id' => $address->id,
                    'name' => 'Canada',
                    'contacts' => 1,
                    'url' => [
                        'index' => env('APP_URL').'/vaults/'.$vault->id.'/reports/addresses/country/canada',
                    ],
                ],
            ],
            $array['countries']->toArray()
        );
    }
}
