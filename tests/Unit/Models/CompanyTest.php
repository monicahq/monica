<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CompanyTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_has_many_users(): void
    {
        $company = Company::factory()->create(['name' => 'Dunder Mifflin']);
        User::factory()->count(2)->create(['company_id' => $company->id]);

        $this->assertTrue($company->users()->exists());
        $this->assertCount(2, $company->users()->get());
    }

    #[Test]
    public function it_belongs_to_an_owner(): void
    {
        $company = Company::factory()->create();
        $owner = User::factory()->create([
            'company_id' => $company->id,
            'email' => 'michael.scott@dundermifflin.com',
        ]);
        $company->owner_user_id = $owner->id;
        $company->save();

        $this->assertTrue($company->owner()->exists());
        $this->assertEquals('michael.scott@dundermifflin.com', $company->owner->email);
    }

    #[Test]
    public function it_has_no_owner_until_one_is_set(): void
    {
        $company = Company::factory()->create();

        $this->assertNull($company->owner);
    }

    #[Test]
    public function it_knows_whether_it_is_on_trial(): void
    {
        $onTrial = Company::factory()->create(['trial_ends_at' => now()->addDays(10)]);
        $expired = Company::factory()->create(['trial_ends_at' => now()->subDay()]);
        $never = Company::factory()->create(['trial_ends_at' => null]);

        $this->assertTrue($onTrial->isOnTrial());
        $this->assertFalse($expired->isOnTrial());
        $this->assertFalse($never->isOnTrial());
    }
}
