<?php

namespace Tests\Unit\Models;

use App\Models\ContactInformationType;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ContactInformationTypeTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_has_one_account()
    {
        $type = ContactInformationType::factory()->create();

        $this->assertTrue($type->account()->exists());
    }

    /** @test */
    public function it_gets_the_default_name()
    {
        $callReason = ContactInformationType::factory()->create([
            'name' => null,
            'name_translation_key' => 'template.label',
        ]);

        $this->assertEquals(
            'template.label',
            $callReason->name
        );
    }

    /** @test */
    public function it_gets_the_custom_name_if_defined()
    {
        $callReason = ContactInformationType::factory()->create([
            'name' => 'this is the real name',
            'name_translation_key' => 'template.label',
        ]);

        $this->assertEquals(
            'this is the real name',
            $callReason->name
        );
    }
}
