<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Attribute extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'information_id',
        'name',
        'type',
        'unit',
        'unit_placement_after',
        'has_default_value',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'has_default_value' => 'boolean',
        'unit_placement_after' => 'boolean',
    ];

    /**
     * Get the information associated with the attribute.
     *
     * @return BelongsTo
     */
    public function information()
    {
        return $this->belongsTo(Information::class);
    }

    /**
     * Get the attribute default values associated with the attribute.
     *
     * @return HasMany
     */
    public function defaultValues()
    {
        return $this->hasMany(AttributeDefaultValue::class);
    }
}
