<?php

namespace App\Models;

use App\Domains\Contact\Dav\VCardResource;
use App\Helpers\ScoutHelper;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Attributes\SearchUsingFullText;
use Laravel\Scout\Attributes\SearchUsingPrefix;
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
     * @var array<int,string>
     */
    protected $fillable = [
        'vault_id',
        'group_type_id',
        'name',
        'uuid',
        'vcard',
        'distant_uuid',
        'distant_etag',
        'distant_uri',
    ];

    /**
     * Get the columns that should receive a unique identifier.
     *
     * @return array
     */
    public function uniqueIds()
    {
        return ['uuid'];
    }

    /**
     * Get the indexable data array for the model.
     *
     *
     * @codeCoverageIgnore
     */
    #[SearchUsingPrefix(['id', 'vault_id'])]
    #[SearchUsingFullText(['name'])]
    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'vault_id' => $this->vault_id,
            'name' => $this->name,
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
     * Get the vault associated with the group.
     */
    public function vault(): BelongsTo
    {
        return $this->belongsTo(Vault::class);
    }

    /**
     * Get the vault associated with the group.
     */
    public function groupType(): BelongsTo
    {
        return $this->belongsTo(GroupType::class);
    }

    /**
     * Get the contacts associated with the group.
     */
    public function contacts(): BelongsToMany
    {
        return $this->belongsToMany(Contact::class);
    }

    /**
     * Get the group's feed item.
     */
    public function feedItem(): MorphOne
    {
        return $this->morphOne(ContactFeedItem::class, 'feedable');
    }

    /**
     * Get the uuid of the group.
     *
     * @return Attribute<string,never>
     */
    protected function uuid(): Attribute
    {
        return Attribute::make(
            get: function (?string $value, array $attributes) {
                if (! isset($attributes['uuid'])) {
                    return tap($this->newUniqueId(), function ($uuid) {
                        $this->forceFill(['uuid' => $uuid]);
                    });
                }

                return $attributes['uuid'];
            }
        );
    }
}
