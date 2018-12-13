<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Instance\Emotion\Emotion;
use App\Models\Instance\Emotion\PrimaryEmotion;
use App\Models\Instance\Emotion\SecondaryEmotion;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class EmotionTest extends TestCase
{
    use DatabaseTransactions;

    public function test_emotion_belongs_to_a_primary_emotion()
    {
        $emotion = factory(Emotion::class)->create([]);

        $this->assertTrue($emotion->primary->exists());

        $this->assertTrue($emotion->secondary->exists());
    }

    public function test_secondary_emotion_belongs_to_a_primary_emotion()
    {
        $secondaryEmotion = factory(SecondaryEmotion::class)->create([]);

        $this->assertTrue($secondaryEmotion->primary->exists());
    }

    public function test_a_primary_emotion_has_multiple_emotions()
    {
        $primaryEmotion = factory(PrimaryEmotion::class)->create([]);
        $secondaryEmotion = factory(SecondaryEmotion::class)->create([
            'emotion_primary_id' => $primaryEmotion->id,
        ]);
        factory(Emotion::class, 3)->create([
            'emotion_primary_id' => $primaryEmotion->id,
            'emotion_secondary_id' => $secondaryEmotion->id,
        ]);

        $this->assertTrue($primaryEmotion->secondaries()->exists());
        $this->assertTrue($primaryEmotion->emotions()->exists());
    }
}
