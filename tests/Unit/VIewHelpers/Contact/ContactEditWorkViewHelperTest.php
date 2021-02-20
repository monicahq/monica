<?php

namespace Tests\Unit\ViewHelpers\Contact;

use App\Http\ViewHelpers\Company\CompanyViewHelper;
use App\Models\Account\Account;
use App\Models\Account\Company;
use App\Models\Company\Answer;
use App\Models\Company\CompanyNews;
use App\Models\Company\Employee;
use App\Models\Company\Question;
use App\Models\Company\Ship;
use App\Models\Company\Skill;
use App\Models\Company\Team;
use App\Models\Contact\Contact;
use App\Services\Company\GuessEmployeeGame\CreateGuessEmployeeGame;
use App\ViewHelpers\Contact\ContactEditWorkViewHelper;
use App\ViewHelpers\Contact\ContactIndexViewHelper;
use Carbon\Carbon;
use GrahamCampbell\TestBenchCore\HelperTrait;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

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
