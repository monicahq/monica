<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Pronoun;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class PronounTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_has_one_account()
    {
        $pronoun = Pronoun::factory()->create();

        $this->assertTrue($pronoun->account()->exists());
    }
}
