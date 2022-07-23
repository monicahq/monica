<?php

namespace Tests\Unit\Models;

use App\Models\ContactInformation;
use App\Models\ContactInformationType;
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

    /** @test */
    public function it_gets_the_name(): void
    {
        $contactInformationType = ContactInformationType::factory()->create([
            'can_be_deleted' => true,
            'name' => 'Facebook',
        ]);
        $contactInformation = ContactInformation::factory()->create([
            'type_id' => $contactInformationType->id,
            'data' => 'Test',
        ]);

        $this->assertEquals(
            'Facebook',
            $contactInformation->name
        );

        $contactInformationType = ContactInformationType::factory()->create([
            'can_be_deleted' => false,
            'name' => 'Facebook',
        ]);
        $contactInformation = ContactInformation::factory()->create([
            'type_id' => $contactInformationType->id,
            'data' => 'Test',
        ]);

        $this->assertEquals(
            'Test',
            $contactInformation->name
        );
    }
}
