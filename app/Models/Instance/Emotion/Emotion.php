<?php

namespace App\Models\Instance\Emotion;

use Exception;
use App\Models\User\User;
use App\Models\Instance\Emotion\PrimaryEmotion;
use App\Models\Instance\Emotion\SecondaryEmotion;
use Sabre\VObject\Component\VCard;
use App\Services\VCard\ImportVCard;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use App\Exceptions\MissingParameterException;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An emotion (ex: Adoration) is defined into 3 categories:
 * - Primary: Love
 * - Secondary: Affection
 * - Tertiary: Adoration
 */
class Emotion extends Model
{
    protected $table = 'emotions';

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    /**
     * Get the primary emotion record associated with the emotion.
     *
     * @return BelongsTo
     */
    public function primary()
    {
        return $this->belongsTo(PrimaryEmotion::class, 'emotion_primary_id');
    }

    /**
     * Get the secondary emotion record associated with the emotion.
     *
     * @return BelongsTo
     */
    public function secondary()
    {
        return $this->belongsTo(SecondaryEmotion::class, 'emotion_secondary_id');
    }
}
