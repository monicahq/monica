<?php

namespace Tests\Unit\Models;

use App\Models\ContactImportantDate;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ContactImportantDateTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_has_one_contact()
    {
        $date = ContactImportantDate::factory()->create();

        $this->assertTrue($date->contact()->exists());
    }

    /** @test */
    public function it_has_one_important_date_type()
    {
        $date = ContactImportantDate::factory()->create();

        $this->assertTrue($date->contactImportantDateType()->exists());
    }
}
