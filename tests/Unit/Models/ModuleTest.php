<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Template;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ModuleTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_has_one_account()
    {
        $template = Template::factory()->create();

        $this->assertTrue($template->account()->exists());
    }
}
