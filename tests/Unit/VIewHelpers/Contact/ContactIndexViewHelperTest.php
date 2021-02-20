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
use App\ViewHelpers\Contact\ContactIndexViewHelper;
use Carbon\Carbon;
use GrahamCampbell\TestBenchCore\HelperTrait;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

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
