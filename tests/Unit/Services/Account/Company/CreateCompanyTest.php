<?php

namespace Tests\Unit\Services\Account\Place;

use Tests\TestCase;
use App\Models\Account\Account;
use App\Models\Account\Company;
use Illuminate\Validation\ValidationException;
use App\Services\Account\Company\CreateCompany;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class CreateCompanyTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_stores_a_company()
    {
        $account = factory(Account::class)->create([]);

        $request = [
            'account_id' => $account->id,
            'name' => 'central perk',
            'website' => 'https://centralperk.com',
            'number_of_employees' => 3,
        ];

        $company = app(CreateCompany::class)->execute($request);

        $this->assertDatabaseHas('companies', [
            'id' => $company->id,
            'account_id' => $account->id,
            'name' => 'central perk',
            'website' => 'https://centralperk.com',
            'number_of_employees' => 3,
        ]);

        $this->assertInstanceOf(
            Company::class,
            $company
        );
    }

    public function test_it_fails_if_wrong_parameters_are_given()
    {
        $account = factory(Account::class)->create([]);

        $request = [
            'street' => '199 Lafayette Street',
        ];

        $this->expectException(ValidationException::class);
        app(CreateCompany::class)->execute($request);
    }
}
