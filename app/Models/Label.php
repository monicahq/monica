<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class Label extends Model
{
    use HasFactory;

    protected $table = 'labels';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'vault_id',
        'name',
        'slug',
        'description',
        'category',
        'color',
        'bg_color',
        'text_color',
    ];

    /**
     * Get the vault associated with the label.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Vault, $this>
     */
    public function vault(): BelongsTo
    {
        return $this->belongsTo(Vault::class);
    }

    /**
     * Get the contacts associated with the label.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany<\App\Models\Contact, $this>
     */
    public function contacts(): BelongsToMany
    {
        return $this->belongsToMany(Contact::class);
    }

    /**
     * Get the label's feed item.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne<\App\Models\ContactFeedItem, $this>
     */
    public function feedItem(): MorphOne
    {
        return $this->morphOne(ContactFeedItem::class, 'feedable');
    }

    /**
     * Get all taggables (polymorphic relationships) for this label.
     * This enables tagging multiple model types: contacts, activities, etc.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\Taggable, $this>
     */
    public function taggables(): HasMany
    {
        return $this->hasMany(Taggable::class, 'label_id');
    }
}
