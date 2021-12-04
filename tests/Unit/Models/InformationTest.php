<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Template;
use App\Models\Attribute;
use App\Models\Information;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class InformationTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_has_one_account()
    {
        $gender = Information::factory()->create();

        $this->assertTrue($gender->account()->exists());
    }

    /** @test */
    public function it_has_many_attributes()
    {
        $information = Information::factory()->create();
        Attribute::factory()->count(2)->create([
            'information_id' => $information->id,
        ]);

        $this->assertTrue($information->attributes()->exists());
    }

    /** @test */
    public function it_has_many_templates()
    {
        $information = Information::factory()->create();

        $template = Template::factory()->create([
            'account_id' => $information->account_id,
        ]);
        $information->templates()->attach(
            $template->id,
            [
                'position' => 0,
            ]
        );

        $this->assertTrue($information->templates()->exists());
    }
}
