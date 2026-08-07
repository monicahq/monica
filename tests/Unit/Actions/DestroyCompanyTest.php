<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\DestroyCompany;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DestroyCompanyTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_destroys_the_company(): void
    {
        $company = Company::factory()->create();
        $owner = User::factory()->create(['company_id' => $company->id]);
        $company->owner_user_id = $owner->id;
        $company->save();

        new DestroyCompany(
            user: $owner,
            company: $company,
        )->execute();

        $this->assertModelMissing($company);
    }

    #[Test]
    public function it_throws_when_the_user_is_not_the_owner(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $company = Company::factory()->create();
        $owner = User::factory()->create(['company_id' => $company->id]);
        $company->owner_user_id = $owner->id;
        $company->save();

        $member = User::factory()->create(['company_id' => $company->id]);

        new DestroyCompany(
            user: $member,
            company: $company,
        )->execute();
    }

    #[Test]
    public function it_throws_when_the_user_belongs_to_another_company(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $company = Company::factory()->create();
        $stranger = User::factory()->create();

        new DestroyCompany(
            user: $stranger,
            company: $company,
        )->execute();
    }
}
