<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
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
     * Get the activity records associated with the account.
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
