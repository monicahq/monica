<?php

namespace Tests\Unit\Models;

use App\Models\ModuleRowField;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ModuleRowFieldTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_belongs_to_a_module_row()
    {
        $field = ModuleRowField::factory()->create();

        $this->assertTrue($field->row()->exists());
    }
}
