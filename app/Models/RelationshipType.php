<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Factories\Relationship;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RelationshipType extends Model
{
    use HasFactory;

    protected $table = 'relationship_types';

    /**
     * Possible types.
     */
    public const TYPE_LOVE = 'family';

    public const TYPE_CHILD = 'child';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'relationship_group_type_id',
        'name',
        'name_translation_key',
        'name_reverse_relationship',
        'name_reverse_relationship_translation_key',
        'can_be_deleted',
        'type',
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
     * Get the group type associated with the relationship type.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\RelationshipGroupType, $this>
     */
    public function groupType(): BelongsTo
    {
        return $this->belongsTo(RelationshipGroupType::class, 'relationship_group_type_id');
    }

    /**
     * Get the name attribute.
     * Relationship type entries have a default name that can be translated.
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

    /**
     * Get the name of the reverse relationship attribute.
     * Relationship type entries have a default name that can be translated.
     * Howerer, if a name is set, it will be used instead of the default.
     * Interestingly, the attribute has to be written in camelCase.
     *
     * @return Attribute<string,string>
     */
    protected function nameReverseRelationship(): Attribute
    {
        return Attribute::make(
            get: function ($value, $attributes) {
                if (! $value) {
                    return __($attributes['name_reverse_relationship_translation_key']);
                }

                return $value;
            },
            set: fn ($value) => $value,
        );
    }
}
