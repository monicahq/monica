<?php

namespace App\Models\Account;

use Illuminate\Database\Eloquent\Model;

class Weather extends Model
{
    protected $table = 'weather';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'weather_json',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'weather_json' => 'array',
    ];

    /**
     * Get the temperature attribute.
     *
     * @return string
     */
    public function getTemperatureAttribute($value)
    {
        $json = $this->weather_json;

        return $json['currently']['temperature'];
    }

    /**
     * Get the weather summary.
     *
     * @return string
     */
    public function getSummaryAttribute($value)
    {
        $json = $this->weather_json;

        return $json['currently']['summary'];
    }

    /**
     * Get the weather summary icon.
     *
     * @return string
     */
    public function getSummaryIconAttribute($value)
    {
        $json = $this->weather_json;

        return $json['currently']['icon'];
    }
}
