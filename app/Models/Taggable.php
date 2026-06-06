<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Polymorphic pivot between Tag and any taggable model.
 *
 * Today that is Contact. In future it can be Activity, Note, etc.
 */
class Taggable extends Model
{
    protected $table = 'taggables';

    protected $fillable = [
        'tag_id',
        'taggable_id',
        'taggable_type',
    ];

    /**
     * The tag attached to this taggable.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Tag, $this>
     */
    public function tag(): BelongsTo
    {
        return $this->belongsTo(Tag::class);
    }

    /**
     * The polymorphic owner (Contact, Activity, etc.)
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo<\Illuminate\Database\Eloquent\Model, $this>
     */
    public function taggable(): MorphTo
    {
        return $this->morphTo();
    }
}
