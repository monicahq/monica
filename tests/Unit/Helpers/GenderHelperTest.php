<?php

namespace Tests\Unit\Helpers;

use Tests\FeatureTestCase;
use App\Helpers\GendersHelper;

class GenderHelperTest extends FeatureTestCase
{
    /** @test */
    public function it_gets_all_the_gender_inputs()
    {
        $this->signIn();

        $genders = GendersHelper::getGendersInput();

        $this->assertCount(4, $genders);
        $this->assertEquals([
            'id' => '',
            'name' => 'No gender',
        ], $genders[0]);
    }
}
