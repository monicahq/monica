<?php

namespace Tests\Unit\Models;

use App\Models\LifeEventType;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class LifeEventTypeTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_belongs_to_a_life_event_category()
    {
        $lifeEventType = LifeEventType::factory()->create();
        $this->assertTrue($lifeEventType->lifeEventCategory()->exists());
    }

    /** @test */
    public function it_gets_the_custom_label_if_defined()
    {
        $lifeEventType = LifeEventType::factory()->create([
            'label' => 'this is the real name',
            'label_translation_key' => 'life_event_category.label',
        ]);

        $this->assertEquals(
            'this is the real name',
            $lifeEventType->label
        );
    }
}
