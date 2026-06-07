<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Polymorphic tagging model.
 * Allows tagging of multiple model types (Contact, Activity, etc.)
 */
class Taggable extends Model
{
    protected $table = 'taggables';

    protected $fillable = [
        'label_id',
        'taggable_id',
        'taggable_type',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the label associated with this taggable.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Label, $this>
     */
    public function label(): BelongsTo
    {
        return $this->belongsTo(Label::class);
    }

    /**
     * Get the taggable model (Contact, Activity, etc.)
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function taggable(): MorphTo
    {
        return $this->morphTo();
    }
}
