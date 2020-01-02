<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Account\Account;
use App\Models\Account\Company;
use App\Models\Contact\Occupation;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class CompanyTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_belongs_to_an_account()
    {
        $account = factory(Account::class)->create([]);
        $company = factory(Company::class)->create([
            'account_id' => $account->id,
        ]);

        $this->assertTrue($company->account()->exists());
    }

    /** @test */
    public function it_has_many_occupations()
    {
        $company = factory(Company::class)->create([]);
        $occupations = factory(Occupation::class)->create([
            'company_id' => $company->id,
        ]);
        $this->assertTrue($company->occupations()->exists());
    }
}
