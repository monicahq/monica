<?php

namespace App\Models\Instance\Emotion;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * An emotion (ex: Adoration) is defined into 3 categories:
 * - Primary: Love
 * - Secondary: Affection
 * - Tertiary: Adoration.
 */
class PrimaryEmotion extends Model
{
    protected $table = 'emotions_primary';

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    /**
     * Get the emotion records associated with the primary emotion.
     *
     * @return HasMany
     */
    public function emotions()
    {
        return $this->hasMany(Emotion::class, 'emotion_primary_id');
    }

    /**
     * Get the secondary records associated with the primary emotion.
     *
     * @return HasMany
     */
    public function secondaries()
    {
        return $this->hasMany(SecondaryEmotion::class, 'emotion_primary_id');
    }
}
