<?php

namespace Tests\Unit\Models;

use App\Models\Module;
use App\Models\ModuleRow;
use App\Models\TemplatePage;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ModuleTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_has_one_account()
    {
        $module = Module::factory()->create();

        $this->assertTrue($module->account()->exists());
    }

    /** @test */
    public function it_has_many_rows(): void
    {
        $module = Module::factory()->create();
        ModuleRow::factory()->create([
            'module_id' => $module->id,
        ]);

        $this->assertTrue($module->rows()->exists());
    }

    /** @test */
    public function it_has_many_template_pages(): void
    {
        $module = Module::factory()->create();

        $templatePage = TemplatePage::factory()->create();
        $module->templatePages()->syncWithoutDetaching([
            $templatePage->id => ['position' => 3],
        ]);

        $this->assertTrue($module->templatePages()->exists());
    }
}
