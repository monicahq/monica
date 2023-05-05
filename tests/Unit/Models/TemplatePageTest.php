<?php

namespace Tests\Unit\Models;

use App\Models\Module;
use App\Models\TemplatePage;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

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

    /** @test */
    public function it_gets_the_default_label()
    {
        $templatePage = TemplatePage::factory()->create([
            'name' => null,
            'name_translation_key' => 'bla',
        ]);

        $this->assertEquals(
            'bla',
            $templatePage->name
        );
    }

    /** @test */
    public function it_gets_the_custom_label_if_defined()
    {
        $templatePage = TemplatePage::factory()->create([
            'name' => 'this is the real name',
            'name_translation_key' => 'bla',
        ]);

        $this->assertEquals(
            'this is the real name',
            $templatePage->name
        );
    }
}
