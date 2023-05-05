<?php

namespace Tests\Unit\Models;

use App\Models\Emotion;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class EmotionTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_has_one_account()
    {
        $emotion = Emotion::factory()->create();

        $this->assertTrue($emotion->account()->exists());
    }

    /** @test */
    public function it_gets_the_default_name()
    {
        $emotion = Emotion::factory()->create([
            'name' => null,
            'name_translation_key' => 'template.label',
        ]);

        $this->assertEquals(
            'template.label',
            $emotion->name
        );
    }

    /** @test */
    public function it_gets_the_custom_name_if_defined()
    {
        $emotion = Emotion::factory()->create([
            'name' => 'this is the real name',
            'name_translation_key' => 'template.label',
        ]);

        $this->assertEquals(
            'this is the real name',
            $emotion->name
        );
    }
}
