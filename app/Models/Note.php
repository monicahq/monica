<?php

namespace App\Models;

use App\Helpers\ScoutHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Laravel\Scout\Attributes\SearchUsingFullText;
use Laravel\Scout\Attributes\SearchUsingPrefix;
use Laravel\Scout\Searchable;

class Note extends Model
{
    use HasFactory;
    use Searchable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'contact_id',
        'vault_id',
        'author_id',
        'emotion_id',
        'title',
        'body',
    ];

    /**
     * Get the indexable data array for the model.
     *
     * @return array
     * @codeCoverageIgnore
     */
    #[SearchUsingPrefix(['id', 'vault_id'])]
    #[SearchUsingFullText(['title', 'body'])]
    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'vault_id' => $this->vault_id,
            'contact_id' => $this->contact_id,
            'title' => $this->title,
            'body' => $this->body,
        ];
    }

    /**
     * When updating a model, this method determines if we should update the search index.
     *
     * @return bool
     */
    public function searchIndexShouldBeUpdated()
    {
        return ScoutHelper::activated();
    }

    /**
     * Get the contact associated with the note.
     *
     * @return BelongsTo
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    /**
     * Get the author associated with the note.
     *
     * @return BelongsTo
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the emotion associated with the note.
     *
     * @return BelongsTo
     */
    public function emotion(): BelongsTo
    {
        return $this->belongsTo(Emotion::class);
    }

    /**
     * Get the note's feed item.
     *
     * @return MorphOne
     */
    public function feedItem(): MorphOne
    {
        return $this->morphOne(ContactFeedItem::class, 'feedable');
    }
}
