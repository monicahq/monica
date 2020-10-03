<?php

namespace Tests\Unit\Controllers\Account\LifeEvent;

use Tests\FeatureTestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class LifeEventCategoriesControllerTest extends FeatureTestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_gets_the_list_of_life_event_categories()
    {
        $user = $this->signin();

        $response = $this->get('settings/personalization/lifeeventcategories');

        $response->assertStatus(200);
    }
}
