<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\ContactImportantDate;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ContactImportantDateTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_has_one_contact()
    {
        $date = ContactImportantDate::factory()->create();

        $this->assertTrue($date->contact()->exists());
    }
}
