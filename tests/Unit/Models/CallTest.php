<?php

namespace Tests\Unit\Models;

use App\Models\Call;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CallTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_has_one_contact()
    {
        $call = Call::factory()->create();

        $this->assertTrue($call->contact()->exists());
    }

    /** @test */
    public function it_has_one_author()
    {
        $call = Call::factory()->create();

        $this->assertTrue($call->author()->exists());
    }

    /** @test */
    public function it_has_one_call_reason()
    {
        $call = Call::factory()->create();

        $this->assertTrue($call->callReason()->exists());
    }
}
