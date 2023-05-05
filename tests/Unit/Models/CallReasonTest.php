<?php

namespace Tests\Unit\Models;

use App\Models\CallReason;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CallReasonTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_belongs_to_an_call_reason_type()
    {
        $callReason = CallReason::factory()->create();
        $this->assertTrue($callReason->callReasonType()->exists());
    }

    /** @test */
    public function it_gets_the_default_label()
    {
        $callReason = CallReason::factory()->create([
            'label' => null,
            'label_translation_key' => 'template.label',
        ]);

        $this->assertEquals(
            'template.label',
            $callReason->label
        );
    }

    /** @test */
    public function it_gets_the_custom_label_if_defined()
    {
        $callReason = CallReason::factory()->create([
            'label' => 'this is the real label',
            'label_translation_key' => 'template.label',
        ]);

        $this->assertEquals(
            'this is the real label',
            $callReason->label
        );
    }
}
