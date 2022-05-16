<?php

namespace Tests\Unit\Models;

use App\Models\ModuleRow;
use App\Models\ModuleRowField;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ModuleRowTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_has_one_module()
    {
        $row = ModuleRow::factory()->create();

        $this->assertTrue($row->module()->exists());
    }

    /** @test */
    public function it_has_many_fields()
    {
        $row = ModuleRow::factory()->create();
        ModuleRowField::factory()->create([
            'module_row_id' => $row->id,
        ]);

        $this->assertTrue($row->fields()->exists());
    }
}
