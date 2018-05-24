<?php

namespace App\Models\Settings\CustomFields;

use App\Account;
use Illuminate\Database\Eloquent\Model;
use App\Models\Settings\CustomFields\CustomField;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomFieldPattern extends Model
{
    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    protected $table = 'custom_field_patterns';

    /**
     * Get the account record associated with the record.
     *
     * @return BelongsTo
     */
    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the custom field records associated with the custom field pattern.
     *
     * @return HasMany
     */
    public function customFields()
    {
        return $this->hasMany(CustomField::class);
    }

    /**
     * Get the name field.
     *
     * @return string
     */
    public function getNameAttribute($value)
    {
        return $value;
    }

    /**
     * Get the icon name field.
     *
     * @return string
     */
    public function getIconNameAttribute($value)
    {
        return $value;
    }
}
