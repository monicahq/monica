<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomFieldType extends Model
{
    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    protected $table = 'default_custom_field_types';

    /**
     * Get the type of the custom field.
     *
     * @return string
     */
    public function getTypeAttribute($value)
    {
        return $value;
    }
}
