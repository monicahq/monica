<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\UpdateCompany;
use App\Enums\SizeRangeEnum;
use App\Enums\UserActionEnum;
use App\Enums\WorkModeEnum;
use App\Jobs\LogUserAction;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpdateCompanyTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_updates_the_company(): void
    {
        Queue::fake();

        $company = Company::factory()->create(['name' => 'Dunder Mifflin']);
        $user = User::factory()->create(['company_id' => $company->id]);

        $result = new UpdateCompany(
            user: $user,
            company: $company,
            name: 'Dunder Mifflin Paper Company',
            legalName: 'Dunder Mifflin Inc.',
            websiteUrl: 'https://dundermifflin.com',
            industry: 'Paper',
            sizeRange: SizeRangeEnum::FiftyOneToTwoHundred,
            workMode: WorkModeEnum::Hybrid,
            timezone: 'America/New_York',
            locale: 'fr_FR',
            currency: 'EUR',
            billingEmail: 'accounting@dundermifflin.com',
        )->execute();

        $this->assertInstanceOf(Company::class, $result);

        $company->refresh();

        $this->assertEquals('Dunder Mifflin Paper Company', $company->name);
        $this->assertEquals('Dunder Mifflin Inc.', $company->legal_name);
        $this->assertEquals('Paper', $company->industry);
        $this->assertEquals(SizeRangeEnum::FiftyOneToTwoHundred, $company->size_range);
        $this->assertEquals(WorkModeEnum::Hybrid, $company->work_mode);
        $this->assertEquals('America/New_York', $company->timezone);
        $this->assertEquals('fr_FR', $company->locale);
        $this->assertEquals('EUR', $company->currency);
        $this->assertEquals('accounting@dundermifflin.com', $company->billing_email);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::CompanyUpdate
                && $job->company->id === $company->id
                && $job->user->id === $user->id
                && $job->parameters === ['name' => 'Dunder Mifflin Paper Company'],
        );
    }

    #[Test]
    public function it_does_not_change_the_slug(): void
    {
        Queue::fake();

        $company = Company::factory()->create(['slug' => 'dunder-mifflin']);
        $user = User::factory()->create(['company_id' => $company->id]);

        new UpdateCompany(
            user: $user,
            company: $company,
            name: 'Michael Scott Paper Company',
        )->execute();

        $this->assertEquals('dunder-mifflin', $company->refresh()->slug);
    }

    #[Test]
    public function it_throws_when_the_user_belongs_to_another_company(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $company = Company::factory()->create();
        $stranger = User::factory()->create();

        new UpdateCompany(
            user: $stranger,
            company: $company,
            name: 'Michael Scott Paper Company',
        )->execute();
    }
}
