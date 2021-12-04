<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Place extends Model
{
    use HasFactory;

    protected $table = 'places';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'placeable_id',
        'placeable_type',
        'street',
        'city',
        'province',
        'postal_code',
        'country',
        'latitude',
        'longitude',
    ];

    /**
     * Get the parent placeable model.
     */
    public function placeable()
    {
        return $this->morphTo();
    }
}
