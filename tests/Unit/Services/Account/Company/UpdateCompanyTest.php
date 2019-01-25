<?php

namespace Tests\Unit\Services\Account\Place;

use Tests\TestCase;
use App\Models\Account\Account;
use App\Models\Account\Company;
use Illuminate\Validation\ValidationException;
use App\Services\Account\Company\UpdateCompany;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class UpdateCompanyTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_updates_a_company()
    {
        $company = factory(Company::class)->create([]);

        $request = [
            'account_id' => $company->account->id,
            'company_id' => $company->id,
            'name' => 'Chandler House',
            'website' => 'https://centralperk.com',
            'number_of_employees' => 300,
        ];

        $companyService = new UpdateCompany;
        $company = $companyService->execute($request);

        $this->assertDatabaseHas('companies', [
            'id' => $company->id,
            'account_id' => $company->account_id,
            'name' => 'Chandler House',
            'website' => 'https://centralperk.com',
            'number_of_employees' => 300,
        ]);

        $this->assertInstanceOf(
            Company::class,
            $company
        );
    }

    public function test_it_fails_if_wrong_parameters_are_given()
    {
        $company = factory(Company::class)->create([]);

        $request = [
            'name' => '199 Lafayette Street',
        ];

        $this->expectException(ValidationException::class);
        (new UpdateCompany)->execute($request);
    }

    public function test_it_throws_an_exception_if_place_is_not_linked_to_account()
    {
        $account = factory(Account::class)->create([]);
        $company = factory(Company::class)->create([]);

        $request = [
            'account_id' => $account->id,
            'company_id' => $company->id,
            'name' => '199 Lafayette Street',
        ];

        $this->expectException(ModelNotFoundException::class);
        (new UpdateCompany)->execute($request);
    }
}
