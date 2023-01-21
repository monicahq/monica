<?php

namespace Tests\Unit\Models;

use App\Models\MoodTrackingEvent;
use App\Models\MoodTrackingParameter;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class MoodTrackingParameterTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_has_one_vault()
    {
        $parameter = MoodTrackingParameter::factory()->create();

        $this->assertTrue($parameter->vault()->exists());
    }

    /** @test */
    public function it_gets_the_default_label()
    {
        $parameter = MoodTrackingParameter::factory()->create([
            'label' => null,
            'label_translation_key' => 'mood_label.label',
        ]);

        $this->assertEquals(
            'mood_label.label',
            $parameter->label
        );
    }

    /** @test */
    public function it_has_many_events(): void
    {
        $parameter = MoodTrackingParameter::factory()->create();
        MoodTrackingEvent::factory()->count(2)->create([
            'mood_tracking_parameter_id' => $parameter->id,
        ]);

        $this->assertTrue($parameter->moodTrackingEvents()->exists());
    }

    /** @test */
    public function it_gets_the_custom_label_if_defined()
    {
        $parameter = MoodTrackingParameter::factory()->create([
            'label' => 'this is the real name',
            'label_translation_key' => 'mood_label.label',
        ]);

        $this->assertEquals(
            'this is the real name',
            $parameter->label
        );
    }
}
