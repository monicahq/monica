<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GroupType extends Model
{
    use HasFactory;

    protected $table = 'group_types';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'account_id',
        'label',
        'label_translation_key',
        'position',
    ];

    /**
     * Get the account record associated with the group type.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Account, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the group type roles associated with the group type.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\GroupTypeRole, $this>
     */
    public function groupTypeRoles(): HasMany
    {
        return $this->hasMany(GroupTypeRole::class);
    }

    /**
     * Get the label attribute.
     * Group type entries have a default label that can be translated.
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
