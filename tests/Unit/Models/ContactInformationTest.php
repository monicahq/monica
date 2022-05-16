<?php

namespace Tests\Unit\Models;

use App\Models\ContactInformation;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

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
