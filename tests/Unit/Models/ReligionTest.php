<?php

namespace Tests\Unit\Models;

use App\Models\Religion;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ReligionTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_belongs_to_an_account()
    {
        $religion = Religion::factory()->create();
        $this->assertTrue($religion->account()->exists());
    }

    /** @test */
    public function it_gets_the_religion_name()
    {
        $religion = Religion::factory()->create([
            'name' => null,
            'translation_key' => 'Sikhism',
        ]);

        $this->assertEquals(
            'Sikhism',
            $religion->name
        );

        $religion = Religion::factory()->create([
            'name' => 'God',
            'translation_key' => null,
        ]);

        $this->assertEquals(
            'God',
            $religion->name
        );
    }
}
