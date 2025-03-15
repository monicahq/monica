<?php

namespace App\Models;

use App\Helpers\ScoutHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Laravel\Scout\Attributes\SearchUsingFullText;
use Laravel\Scout\Searchable;

class Note extends Model
{
    use HasFactory;
    use Searchable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
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
     * @codeCoverageIgnore
     */
    #[SearchUsingFullText(['title', 'body'], ['expanded' => true])]
    public function toSearchableArray(): array
    {
        return array_merge(ScoutHelper::id($this), [
            'contact_id' => (string) $this->contact_id,
            'title' => $this->title ?? '',
            'body' => $this->body ?? '',
        ]);
    }

    /**
     * When updating a model, this method determines if we should update the search index.
     *
     * @return bool
     */
    public function searchIndexShouldBeUpdated()
    {
        return ScoutHelper::isActivated();
    }

    /**
     * Get the contact associated with the note.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Contact, $this>
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    /**
     * Get the author associated with the note.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the emotion associated with the note.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Emotion, $this>
     */
    public function emotion(): BelongsTo
    {
        return $this->belongsTo(Emotion::class);
    }

    /**
     * Get the note's feed item.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne<\App\Models\ContactFeedItem, $this>
     */
    public function feedItem(): MorphOne
    {
        return $this->morphOne(ContactFeedItem::class, 'feedable');
    }
}
