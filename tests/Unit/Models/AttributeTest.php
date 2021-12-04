<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Attribute;
use App\Models\AttributeDefaultValue;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class AttributeTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_has_one_information()
    {
        $gender = Attribute::factory()->create();

        $this->assertTrue($gender->information()->exists());
    }

    /** @test */
    public function it_has_many_default_values()
    {
        $gender = Attribute::factory()->create();
        AttributeDefaultValue::factory()->count(2)->create([
            'attribute_id' => $gender->id,
        ]);

        $this->assertTrue($gender->defaultValues()->exists());
    }
}
