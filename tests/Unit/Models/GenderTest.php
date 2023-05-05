<?php

namespace Tests\Unit\Models;

use App\Models\Gender;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class GenderTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_has_one_account()
    {
        $gender = Gender::factory()->create();

        $this->assertTrue($gender->account()->exists());
    }

    /** @test */
    public function it_gets_the_default_label()
    {
        $gender = Gender::factory()->create([
            'name' => null,
            'name_translation_key' => 'bla',
        ]);

        $this->assertEquals(
            'bla',
            $gender->name
        );
    }

    /** @test */
    public function it_gets_the_custom_label_if_defined()
    {
        $gender = Gender::factory()->create([
            'name' => 'this is the real name',
            'name_translation_key' => 'bla',
        ]);

        $this->assertEquals(
            'this is the real name',
            $gender->name
        );
    }
}
