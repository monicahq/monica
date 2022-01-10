<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Module;
use App\Models\TemplatePage;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class TemplatePageTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_has_one_template()
    {
        $templatePage = TemplatePage::factory()->create();

        $this->assertTrue($templatePage->template()->exists());
    }

    /** @test */
    public function it_has_many_modules(): void
    {
        $templatePage = TemplatePage::factory()->create();

        $module = Module::factory()->create();
        $templatePage->modules()->sync([$module->id]);

        $this->assertTrue($templatePage->modules()->exists());
    }
}
