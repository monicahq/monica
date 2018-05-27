<?php

namespace App\Models\Settings\CustomFields;

use App\Account;
use Illuminate\Database\Eloquent\Model;
use App\Models\Settings\CustomFields\CustomField;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Field extends Model
{
    /**
     * The valid field types that we accept.
     *
     * @var array
     */
    protected $validFieldTypes = [
        'text',
        'textarea',
        'date',
        'dropdown',
        'radiobutton',
        'checkbox',
    ];

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

    /**
     * Return true if the field contains a list of choices the user has to
     * chose from.
     *
     * @return bool
     */
    pubic function hasFieldChoices(): bool
    {
        return;
    }
}
