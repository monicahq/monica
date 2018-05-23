<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
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
        return $this->belongsTo('App\Account');
    }

    /**
     * Get the custom field record associated with the field.
     *
     * @return BelongsTo
     */
    public function customField()
    {
        return $this->belongsTo('App\CustomField');
    }

    /**
     * Get the default custom field type record associated with the field.
     *
     * @return BelongsTo
     */
    public function customFieldType()
    {
        return $this->belongsTo('App\CustomFieldType');
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
