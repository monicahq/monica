<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Company;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class CompanyTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_has_one_vault()
    {
        $company = Company::factory()->create();

        $this->assertTrue($company->vault()->exists());
    }
}
