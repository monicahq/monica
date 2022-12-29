<?php

namespace Tests\Unit\Models;

use App\Models\Company;
use App\Models\Contact;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CompanyTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_has_one_vault()
    {
        $company = Company::factory()->create();

        $this->assertTrue($company->vault()->exists());
    }

    /** @test */
    public function it_has_many_contacts(): void
    {
        $company = Company::factory()->create();
        Contact::factory()->count(2)->create([
            'company_id' => $company->id,
        ]);

        $this->assertTrue($company->contacts()->exists());
    }
}
