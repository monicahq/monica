<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Contact\Call;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class CallTest extends TestCase
{
    use DatabaseTransactions;

    public function test_call_note_returns_null_if_undefined()
    {
        $call = new Call;

        $this->assertNull($call->getParsedContentAttribute());
    }

    public function test_call_note_returns_html_if_defined()
    {
        $call = new Call;
        $call->content = '### test';

        $this->assertEquals(
            '<h3>test</h3>',
            $call->getParsedContentAttribute()
        );
    }
}
