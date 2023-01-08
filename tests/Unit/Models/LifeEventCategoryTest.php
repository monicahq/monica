<?php

namespace Tests\Unit\Models;

use App\Models\LifeEventCategory;
use App\Models\LifeEventType;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class LifeEventCategoryTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_belongs_to_a_vault()
    {
        $lifeEventCategory = LifeEventCategory::factory()->create();
        $this->assertTrue($lifeEventCategory->vault()->exists());
    }

    /** @test */
    public function it_has_many_types()
    {
        $lifeEventCategory = LifeEventCategory::factory()->create();
        LifeEventType::factory(2)->create([
            'life_event_category_id' => $lifeEventCategory->id,
        ]);

        $this->assertTrue($lifeEventCategory->lifeEventTypes()->exists());
    }

    /** @test */
    public function it_gets_the_default_label()
    {
        $lifeEventCategory = LifeEventCategory::factory()->create([
            'label' => null,
            'label_translation_key' => 'life_event_category.label',
        ]);

        $this->assertEquals(
            'life_event_category.label',
            $lifeEventCategory->label
        );
    }

    /** @test */
    public function it_gets_the_custom_label_if_defined()
    {
        $lifeEventCategory = LifeEventCategory::factory()->create([
            'label' => 'this is the real name',
            'label_translation_key' => 'life_event_category.label',
        ]);

        $this->assertEquals(
            'this is the real name',
            $lifeEventCategory->label
        );
    }
}
