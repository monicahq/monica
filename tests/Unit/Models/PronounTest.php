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
}
