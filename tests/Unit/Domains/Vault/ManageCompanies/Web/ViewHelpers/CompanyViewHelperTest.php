<?php

namespace Tests\Unit\Domains\Vault\ManageCompanies\Web\ViewHelpers;

use Tests\TestCase;
use App\Models\Vault;
use App\Models\Company;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Vault\ManageCompanies\Web\ViewHelpers\CompanyViewHelper;

class CompanyViewHelperTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_gets_information_necessary_to_load_the_view(): void
    {
        $vault = Vault::factory()->create();
        Company::factory()->create([
            'vault_id' => $vault->id,
        ]);

        $array = CompanyViewHelper::data($vault);
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

        $array = CompanyViewHelper::dto($vault, $company);
        $this->assertEquals(
            [
                'id' => $company->id,
                'name' => $company->name,
                'type' => $company->type,
            ],
            $array
        );
    }
}
