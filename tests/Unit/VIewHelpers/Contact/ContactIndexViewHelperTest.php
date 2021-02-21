<?php

namespace Tests\Unit\ViewHelpers\Contact;

use Tests\TestCase;
use App\Models\Account\Account;
use App\Models\Account\Company;
use App\Models\Contact\Contact;
use App\ViewHelpers\Contact\ContactIndexViewHelper;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ContactIndexViewHelperTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_gets_information_about_the_contact(): void
    {
        $account = factory(Account::class)->create([]);
        $ross = factory(Contact::class)->create([
            'account_id' => $account->id,
        ]);
        $array = ContactIndexViewHelper::information($ross);
        $this->assertEquals(
            [
                'work' => [
                    'job' => null,
                    'company' => null,
                ],
            ],
            $array
        );

        // adding a job
        $company = factory(Company::class)->create([
            'account_id' => $account->id,
            'name' => 'Lawyers associate',
        ]);
        $ross->company_id = $company->id;
        $ross->save();

        $array = ContactIndexViewHelper::information($ross->refresh());
        $this->assertEquals(
            [
                'work' => [
                    'job' => null,
                    'company' => 'Lawyers associate',
                ],
            ],
            $array
        );
    }
}
