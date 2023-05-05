<?php

namespace Tests\Unit\Models;

use App\Models\CallReason;
use App\Models\CallReasonType;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CallReasonTypeTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_belongs_to_an_account()
    {
        $callReasonType = CallReasonType::factory()->create();
        $this->assertTrue($callReasonType->account()->exists());
    }

    /** @test */
    public function it_has_many_call_reasons()
    {
        $callReasonType = CallReasonType::factory()->create();
        CallReason::factory(2)->create([
            'call_reason_type_id' => $callReasonType->id,
        ]);

        $this->assertTrue($callReasonType->callReasons()->exists());
    }

    /** @test */
    public function it_gets_the_default_label()
    {
        $callReasonType = CallReasonType::factory()->create([
            'label' => null,
            'label_translation_key' => 'template.label',
        ]);

        $this->assertEquals(
            'template.label',
            $callReasonType->label
        );
    }

    /** @test */
    public function it_gets_the_custom_label_if_defined()
    {
        $callReasonType = CallReasonType::factory()->create([
            'label' => 'this is the real label',
            'label_translation_key' => 'template.label',
        ]);

        $this->assertEquals(
            'this is the real label',
            $callReasonType->label
        );
    }
}
