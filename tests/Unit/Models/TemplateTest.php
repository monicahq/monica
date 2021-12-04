<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Template;
use App\Models\Information;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class TemplateTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_has_one_account()
    {
        $template = Template::factory()->create();

        $this->assertTrue($template->account()->exists());
    }

    /** @test */
    public function it_has_many_informations()
    {
        $template = Template::factory()->create();

        $information = Information::factory()->create([
            'account_id' => $template->account_id,
        ]);
        $template->informations()->attach(
            $information->id,
            [
                'position' => 0,
            ]
        );

        $this->assertTrue($template->informations()->exists());
    }
}
