<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Account\Account;
use App\Models\Account\Company;
use App\Models\Contact\Contact;
use App\Models\Contact\Occupation;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class OccupationTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_belongs_to_an_account()
    {
        $account = factory(Account::class)->create([]);
        $occupation = factory(Occupation::class)->create([
            'account_id' => $account->id,
        ]);

        $this->assertTrue($occupation->account()->exists());
    }

    /** @test */
    public function it_belongs_to_a_contact()
    {
        $contact = factory(Contact::class)->create([]);
        $occupation = factory(Occupation::class)->create([
            'contact_id' => $contact->id,
        ]);
        $this->assertTrue($occupation->contact()->exists());
    }

    /** @test */
    public function it_belongs_to_a_company()
    {
        $company = factory(Company::class)->create([]);
        $occupation = factory(Occupation::class)->create([
            'company_id' => $company->id,
        ]);
        $this->assertTrue($occupation->company()->exists());
    }
}
