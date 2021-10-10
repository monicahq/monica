<?php

namespace Tests\Unit\Services\Account\Company;

use Tests\TestCase;
use App\Models\User\User;
use function Safe\json_encode;
use App\Models\Account\Account;
use App\Models\Account\Company;
use Illuminate\Support\Facades\Queue;
use App\Jobs\AuditLog\LogAccountAudit;
use Illuminate\Validation\ValidationException;
use App\Services\Account\Company\CreateCompany;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class CreateCompanyTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_stores_a_company()
    {
        Queue::fake();

        $account = factory(Account::class)->create([]);
        $user = factory(User::class)->create([
            'account_id' => $account->id,
        ]);

        $request = [
            'account_id' => $account->id,
            'author_id' => $user->id,
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

        Queue::assertPushed(LogAccountAudit::class, function ($job) use ($user) {
            return $job->auditLog['action'] === 'company_created' &&
                $job->auditLog['author_id'] === $user->id &&
                $job->auditLog['about_contact_id'] === null &&
                $job->auditLog['should_appear_on_dashboard'] === true &&
                $job->auditLog['objects'] === json_encode([
                    'name' => 'central perk',
                ]);
        });
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given()
    {
        $account = factory(Account::class)->create([]);

        $request = [
            'street' => '199 Lafayette Street',
        ];

        $this->expectException(ValidationException::class);
        app(CreateCompany::class)->execute($request);
    }
}
