<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Call extends Model
{
    use HasFactory;

    protected $table = 'calls';

    /**
     * Possible type.
     */
    public const TYPE_AUDIO = 'audio';
    public const TYPE_VIDEO = 'video';
    public const INITIATOR_ME = 'me';
    public const INITIATOR_CONTACT = 'contact';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'contact_id',
        'call_reason_id',
        'author_id',
        'emotion_id',
        'author_name',
        'called_at',
        'duration',
        'type',
        'answered',
        'who_initiated',
        'description',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'answered' => 'boolean',
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'called_at',
    ];

    /**
     * Get the contact associated with the call.
     *
     * @return BelongsTo
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    /**
     * Get the author associated with the call.
     *
     * @return BelongsTo
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the call reason associated with the call.
     *
     * @return BelongsTo
     */
    public function callReason(): BelongsTo
    {
        return $this->belongsTo(CallReason::class, 'call_reason_id');
    }

    /**
     * Get the emotion associated with the call.
     *
     * @return BelongsTo
     */
    public function emotion(): BelongsTo
    {
        return $this->belongsTo(Emotion::class);
    }
}
