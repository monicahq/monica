<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class Tag extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'vault_id',
        'name',
        'slug',
        'tag_category', // NEW: optional group e.g. "Personal", "Work"
        'color',        // NEW: hex color for API consumers e.g. "#FF5733"
    ];

    /**
     * Get the vault associated with the journal tag.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Vault, $this>
     */
    public function vault(): BelongsTo
    {
        return $this->belongsTo(Vault::class);
    }

    /**
     * Get the posts associated with the journal tag.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany<\App\Models\Post, $this>
     */
    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class);
    }

    /**
     * Get the journal tag's feed item.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne<\App\Models\ContactFeedItem, $this>
     */
    public function feedItem(): MorphOne
    {
        return $this->morphOne(ContactFeedItem::class, 'feedable');
    }

    /**
     * Get the contacts associated with the tag.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany<\App\Models\Contact, $this>
     */
    public function contacts(): BelongsToMany
    {
        return $this->belongsToMany(Contact::class);
    }

    /**
     * Polymorphic relation to the taggables pivot.
     * Allows tags to be attached to any model in the future
     * (contacts today, activities/notes tomorrow).
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany<\App\Models\Taggable, $this>
     */
    public function taggables(): MorphMany
    {
        // We store the inverse via the Taggable model
        return $this->hasMany(Taggable::class);
    }
}
