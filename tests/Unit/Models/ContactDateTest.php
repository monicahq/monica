<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\ContactDate;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ContactDateTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_has_one_contact()
    {
        $date = ContactDate::factory()->create();

        $this->assertTrue($date->contact()->exists());
    }
}
