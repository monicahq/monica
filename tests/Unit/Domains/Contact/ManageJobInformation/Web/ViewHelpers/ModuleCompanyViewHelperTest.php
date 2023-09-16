<?php

namespace Tests\Unit\Domains\Contact\ManageJobInformation\Web\ViewHelpers;

use App\Domains\Contact\ManageJobInformation\Web\ViewHelpers\ModuleCompanyViewHelper;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Vault;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ModuleCompanyViewHelperTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_gets_the_data_needed_for_the_view(): void
    {
        $contact = Contact::factory()->company()->create([
            'job_position' => 'CEO',
        ]);

        $array = ModuleCompanyViewHelper::data($contact);

        $this->assertEquals(
            3,
            count($array)
        );

        $this->assertArrayHasKey('job_position', $array);
        $this->assertArrayHasKey('company', $array);
        $this->assertArrayHasKey('url', $array);

        $this->assertEquals(
            [
                'job_position' => 'CEO',
                'company' => [
                    'id' => $contact->company->id,
                    'name' => $contact->company->name,
                    'type' => $contact->company->type,
                    'selected' => true,
                ],
                'url' => [
                    'index' => env('APP_URL').'/vaults/'.$contact->vault->id.'/contacts/'.$contact->id.'/companies/list',
                    'update' => env('APP_URL').'/vaults/'.$contact->vault->id.'/contacts/'.$contact->id.'/jobInformation',
                    'destroy' => env('APP_URL').'/vaults/'.$contact->vault->id.'/contacts/'.$contact->id.'/jobInformation',
                ],
            ],
            $array
        );
    }

    /** @test */
    public function it_gets_all_the_companies_in_the_vault(): void
    {
        $vault = Vault::factory()->create();
        $company = Company::factory()->create([
            'vault_id' => $vault->id,
        ]);
        $contact = Contact::factory()->create([
            'vault_id' => $vault->id,
            'company_id' => $company->id,
        ]);

        $collection = ModuleCompanyViewHelper::list($vault, $contact);
        $this->assertEquals(
            [
                0 => [
                    'id' => $company->id,
                    'name' => $company->name,
                    'type' => $company->type,
                    'selected' => true,
                ],
            ],
            $collection->toArray()
        );
    }
}
