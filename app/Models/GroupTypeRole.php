<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GroupTypeRole extends Model
{
    use HasFactory;

    protected $table = 'group_type_roles';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'group_type_id',
        'label',
        'label_translation_key',
        'position',
    ];

    /**
     * Get the group type record associated with the group type role.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\GroupType, $this>
     */
    public function groupType(): BelongsTo
    {
        return $this->belongsTo(GroupType::class);
    }

    /**
     * Get the label attribute.
     * Group type role entries have a default label that can be translated.
     * Howerer, if a label is set, it will be used instead of the default.
     *
     * @return Attribute<string,string>
     */
    protected function label(): Attribute
    {
        return Attribute::make(
            get: function ($value, $attributes) {
                if (! $value) {
                    return __($attributes['label_translation_key']);
                }

                return $value;
            },
            set: fn ($value) => $value,
        );
    }
}
