<?php

namespace Tests\Unit\Helpers;

use App\Helpers\GendersHelper;
use Tests\FeatureTestCase;

class GenderHelperTest extends FeatureTestCase
{
    public function test_getting_gender_input()
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
