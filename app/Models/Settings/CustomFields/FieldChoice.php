<?php

namespace App\Models\Settings\CustomFields;

use App\Account;
use Illuminate\Database\Eloquent\Model;
use App\Models\Settings\CustomFields\Field;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FieldChoice extends Model
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
        'is_default' => 'boolean',
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
     * Get the field record associated with the field.
     *
     * @return BelongsTo
     */
    public function field()
    {
        return $this->belongsTo(Field::class);
    }

    /**
     * Get the value of the field choice.
     *
     * @return string
     */
    public function getValueAttribute($value)
    {
        return $value;
    }

    /**
     * Get the default status of the custom field.
     *
     * @return string
     */
    public function getIsDefaultAttribute($value)
    {
        return $value;
    }
}
