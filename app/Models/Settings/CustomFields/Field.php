<?php

namespace App\Models\Settings\CustomFields;

use App\Account;
use Illuminate\Database\Eloquent\Model;
use App\Models\Settings\CustomFields\CustomField;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Settings\CustomFields\DefaultCustomFieldType;

class Field extends Model
{
    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'required' => 'boolean',
    ];

    /**
     * Get the account record associated with the field.
     *
     * @return BelongsTo
     */
    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the field record associated with the field.
     *
     * @return BelongsTo
     */
    public function customField()
    {
        return $this->belongsTo(CustomField::class);
    }

    /**
     * Get the default custom field type record associated with the field.
     *
     * @return BelongsTo
     */
    public function defaultCustomFieldType()
    {
        return $this->belongsTo(DefaultCustomFieldType::class);
    }

    /**
     * Get the name of the field.
     *
     * @return string
     */
    public function getNameAttribute($value)
    {
        return $value;
    }

    /**
     * Get the required status of the field.
     *
     * @return string
     */
    public function getIsRequiredAttribute($value)
    {
        return $value;
    }

    /**
     * Get the description of the field.
     *
     * @return string
     */
    public function getDescriptionAttribute($value)
    {
        return $value;
    }
}
