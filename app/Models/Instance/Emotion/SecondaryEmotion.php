<?php

namespace App\Models\Instance\Emotion;

use Exception;
use App\Models\User\User;
use App\Models\Instance\Emotion\Emotion;
use App\Models\Instance\Emotion\PrimaryEmotion;
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
class SecondaryEmotion extends Model
{
    protected $table = 'emotions_secondary';

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    /**
     * Get the primary emotion record associated with the secondary emotion.
     *
     * @return BelongsTo
     */
    public function primary()
    {
        return $this->belongsTo(PrimaryEmotion::class, 'emotion_primary_id');
    }

    /**
     * Get the emotion records associated with the secondary emotion.
     *
     * @return HasMany
     */
    public function emotions()
    {
        return $this->hasMany(Emotion::class);
    }
}
