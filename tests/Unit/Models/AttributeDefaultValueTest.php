<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\AttributeDefaultValue;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class AttributeDefaultValueTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_has_one_attribute()
    {
        $defaultValue = AttributeDefaultValue::factory()->create();

        $this->assertTrue($defaultValue->attribute()->exists());
    }
}
