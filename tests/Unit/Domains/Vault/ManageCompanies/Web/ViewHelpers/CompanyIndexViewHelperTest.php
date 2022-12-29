<?php

namespace Tests\Unit\Domains\Vault\ManageCompanies\Web\ViewHelpers;

use App\Domains\Vault\ManageCompanies\Web\ViewHelpers\CompanyIndexViewHelper;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Vault;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CompanyIndexViewHelperTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_gets_information_necessary_to_load_the_view(): void
    {
        $vault = Vault::factory()->create();
        Company::factory()->create([
            'vault_id' => $vault->id,
        ]);

        $array = CompanyIndexViewHelper::data($vault);
        $this->assertCount(
            1,
            $array
        );
        $this->assertArrayHasKey('companies', $array);
    }

    /** @test */
    public function it_gets_the_data_needed_for_the_data_transfer_object(): void
    {
        $vault = Vault::factory()->create();
        $company = Company::factory()->create([
            'vault_id' => $vault->id,
        ]);
        $contact = Contact::factory()->create([
            'vault_id' => $vault->id,
            'company_id' => $company->id,
        ]);

        $array = CompanyIndexViewHelper::dto($vault, $company);
        $this->assertEquals(
            $company->id,
            $array['id']
        );
        $this->assertEquals(
            $company->name,
            $array['name']
        );
        $this->assertEquals(
            [
                0 => [
                    'id' => $contact->id,
                    'avatar' => $contact->avatar,
                    'url' => [
                        'show' => env('APP_URL').'/vaults/'.$contact->vault_id.'/contacts/'.$contact->id,
                    ],
                ],
            ],
            $array['contacts']->toArray()
        );
        $this->assertEquals(
            [
                'show' => env('APP_URL').'/vaults/'.$vault->id.'/companies/'.$company->id,
            ],
            $array['url']
        );
    }
}
