<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\ContactInformation;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ContactInformationTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_has_one_contact()
    {
        $info = ContactInformation::factory()->create();

        $this->assertTrue($info->contact()->exists());
    }

    /** @test */
    public function it_has_one_type()
    {
        $info = ContactInformation::factory()->create();

        $this->assertTrue($info->contactInformationType()->exists());
    }
}
