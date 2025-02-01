<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RelationshipGroupType extends Model
{
    use HasFactory;

    protected $table = 'relationship_group_types';

    /**
     * Possible types.
     */
    public const TYPE_FAMILY = 'family';

    public const TYPE_LOVE = 'love';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'account_id',
        'name',
        'name_translation_key',
        'type',
        'can_be_deleted',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'can_be_deleted' => 'boolean',
    ];

    /**
     * Get the account associated with the relationship type.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Account, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the account associated with the relationship type.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\RelationshipType, $this>
     */
    public function types(): HasMany
    {
        return $this->hasMany(RelationshipType::class);
    }

    /**
     * Get the name attribute.
     * Relationship group type entries have a default name that can be translated.
     * Howerer, if a name is set, it will be used instead of the default.
     *
     * @return Attribute<string,string>
     */
    protected function name(): Attribute
    {
        return Attribute::make(
            get: function ($value, $attributes) {
                if (! $value) {
                    return __($attributes['name_translation_key']);
                }

                return $value;
            },
            set: fn ($value) => $value,
        );
    }
}
