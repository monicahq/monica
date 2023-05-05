<?php

namespace Tests\Unit\Models;

use App\Models\Pronoun;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PronounTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_has_one_account()
    {
        $pronoun = Pronoun::factory()->create();

        $this->assertTrue($pronoun->account()->exists());
    }

    /** @test */
    public function it_gets_the_default_label()
    {
        $pronoun = Pronoun::factory()->create([
            'name' => null,
            'name_translation_key' => 'bla',
        ]);

        $this->assertEquals(
            'bla',
            $pronoun->name
        );
    }

    /** @test */
    public function it_gets_the_custom_label_if_defined()
    {
        $pronoun = Pronoun::factory()->create([
            'name' => 'this is the real name',
            'name_translation_key' => 'bla',
        ]);

        $this->assertEquals(
            'this is the real name',
            $pronoun->name
        );
    }
}
