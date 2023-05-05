<?php

namespace Tests\Unit\Models;

use App\Models\GiftOccasion;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class GiftOccasionTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_has_one_account()
    {
        $occasion = GiftOccasion::factory()->create();

        $this->assertTrue($occasion->account()->exists());
    }

    /** @test */
    public function it_gets_the_default_label()
    {
        $giftOccasion = GiftOccasion::factory()->create([
            'label' => null,
            'label_translation_key' => 'template.label',
        ]);

        $this->assertEquals(
            'template.label',
            $giftOccasion->label
        );
    }

    /** @test */
    public function it_gets_the_custom_label_if_defined()
    {
        $giftOccasion = GiftOccasion::factory()->create([
            'label' => 'this is the real label',
            'label_translation_key' => 'template.label',
        ]);

        $this->assertEquals(
            'this is the real label',
            $giftOccasion->label
        );
    }
}
