<?php

namespace Tests\Unit\Models;

use App\Models\GiftState;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class GiftStateTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_has_one_account()
    {
        $state = GiftState::factory()->create();

        $this->assertTrue($state->account()->exists());
    }

    /** @test */
    public function it_gets_the_default_label()
    {
        $giftState = GiftState::factory()->create([
            'label' => null,
            'label_translation_key' => 'template.label',
        ]);

        $this->assertEquals(
            'template.label',
            $giftState->label
        );
    }

    /** @test */
    public function it_gets_the_custom_label_if_defined()
    {
        $giftState = GiftState::factory()->create([
            'label' => 'this is the real label',
            'label_translation_key' => 'template.label',
        ]);

        $this->assertEquals(
            'this is the real label',
            $giftState->label
        );
    }
}
