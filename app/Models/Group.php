<?php

namespace App\Models;

use App\Domains\Contact\Dav\VCardResource;
use App\Helpers\ScoutHelper;
use App\Traits\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Attributes\SearchUsingFullText;
use Laravel\Scout\Searchable;

class Group extends VCardResource
{
    use HasFactory;
    use HasUuids;
    use Searchable;
    use SoftDeletes;

    protected $table = 'groups';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'vault_id',
        'group_type_id',
        'name',
        'vcard',
        'distant_uuid',
        'distant_etag',
        'distant_uri',
    ];

    /**
     * Get the indexable data array for the model.
     *
     * @codeCoverageIgnore
     */
    #[SearchUsingFullText(['name'], ['expanded' => true])]
    public function toSearchableArray(): array
    {
        return array_merge(ScoutHelper::id($this), [
            'name' => $this->name ?? '',
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
     * Get the vault associated with the group.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Vault, $this>
     */
    public function vault(): BelongsTo
    {
        return $this->belongsTo(Vault::class);
    }

    /**
     * Get the vault associated with the group.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\GroupType, $this>
     */
    public function groupType(): BelongsTo
    {
        return $this->belongsTo(GroupType::class);
    }

    /**
     * Get the contacts associated with the group.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany<\App\Models\Contact, $this>
     */
    public function contacts(): BelongsToMany
    {
        return $this->belongsToMany(Contact::class);
    }

    /**
     * Get the group's feed item.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne<\App\Models\ContactFeedItem, $this>
     */
    public function feedItem(): MorphOne
    {
        return $this->morphOne(ContactFeedItem::class, 'feedable');
    }
}
