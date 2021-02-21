<?php

namespace Tests\Unit\ViewHelpers\Contact;

use Tests\TestCase;
use App\Models\Account\Account;
use App\Models\Account\Company;
use App\ViewHelpers\Contact\ContactEditWorkViewHelper;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ContactEditWorkViewHelperTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_gets_all_the_companies_in_the_account(): void
    {
        $account = factory(Account::class)->create([]);
        $company = factory(Company::class)->create([
            'account_id' => $account->id,
            'name' => 'Lawyers Associate',
        ]);

        $collection = ContactEditWorkViewHelper::companies($account);
        $this->assertEquals(
            [
                0 => [
                    'id' => $company->id,
                    'name' => 'Lawyers Associate',
                ],
            ],
            $collection->toArray()
        );
    }
}
