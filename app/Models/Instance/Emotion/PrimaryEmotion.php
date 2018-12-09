<?php

namespace App\Models\Instance\Emotion;

use Exception;
use App\Models\User\User;
use App\Models\Instance\Emotion\Emotion;
use App\Models\Instance\Emotion\SecondaryEmotion;
use Sabre\VObject\Component\VCard;
use App\Services\VCard\ImportVCard;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use App\Exceptions\MissingParameterException;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Contracts\Filesystem\FileNotFoundException;

/**
 * An emotion (ex: Adoration) is defined into 3 categories:
 * - Primary: Love
 * - Secondary: Affection
 * - Tertiary: Adoration
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
