<?php

namespace App\Models\Settings\CustomFields;

use App\Account;
use Illuminate\Database\Eloquent\Model;
use App\Models\Settings\CustomFields\CustomField;
use App\Models\Settings\CustomFields\CustomFieldType;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
     * Get the account record associated with the custom field.
     *
     * @return BelongsTo
     */
    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the custom field record associated with the field.
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
    public function customFieldType()
    {
        return $this->belongsTo(CustomFieldType::class);
    }

    /**
     * Get the name of the custom field.
     *
     * @return string
     */
    public function getNameAttribute($value)
    {
        return $value;
    }

    /**
     * Get the required status of the custom field.
     *
     * @return string
     */
    public function getIsRequiredAttribute($value)
    {
        return $value;
    }
}
