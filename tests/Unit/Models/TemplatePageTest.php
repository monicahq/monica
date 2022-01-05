<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
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
}
