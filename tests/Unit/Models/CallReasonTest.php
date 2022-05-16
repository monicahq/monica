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
}
