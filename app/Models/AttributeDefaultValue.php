<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AttributeDefaultValue extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'attribute_id', 'value',
    ];

    /**
     * Get the attribute associated with the attribute default value.
     *
     * @return BelongsTo
     */
    public function attribute()
    {
        return $this->belongsTo(Attribute::class);
    }
}
