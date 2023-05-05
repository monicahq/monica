<?php

namespace Tests\Unit\Models;

use App\Models\AddressType;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AddressTypeTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_has_one_account()
    {
        $type = AddressType::factory()->create();

        $this->assertTrue($type->account()->exists());
    }

    /** @test */
    public function it_gets_the_default_name()
    {
        $type = AddressType::factory()->create([
            'name' => null,
            'name_translation_key' => 'template.label',
        ]);

        $this->assertEquals(
            'template.label',
            $type->name
        );
    }

    /** @test */
    public function it_gets_the_custom_name_if_defined()
    {
        $type = AddressType::factory()->create([
            'name' => 'this is the real name',
            'name_translation_key' => 'template.label',
        ]);

        $this->assertEquals(
            'this is the real name',
            $type->name
        );
    }
}
