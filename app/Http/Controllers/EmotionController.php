<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Instance\Emotion\Emotion;
use App\Models\Instance\Emotion\PrimaryEmotion;
use App\Models\Instance\Emotion\SecondaryEmotion;
use App\Http\Resources\Emotion\Emotion as EmotionResource;

class EmotionController extends Controller
{
    /**
     * Get the list of primary emotions.
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function primaries()
    {
        return EmotionResource::collection(PrimaryEmotion::get());
    }

    /**
     * Get the list of secondary emotions.
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function secondaries(Request $request, $primaryEmotionId)
    {
        $secondaries = SecondaryEmotion::where('emotion_primary_id', $primaryEmotionId)
            ->get();

        return EmotionResource::collection($secondaries);
    }

    /**
     * Get the list of emotions.
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function emotions(Request $request, $primaryEmotionId, $secondaryEmotionId)
    {
        $emotions = Emotion::where('emotion_secondary_id', $secondaryEmotionId)
            ->get();

        return EmotionResource::collection($emotions);
    }
}
