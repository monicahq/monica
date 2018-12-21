<?php

namespace App\Models\Account;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    /**
     * Get the account record associated with the weather data.
     *
     * @return BelongsTo
     */
    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the place record associated with the weather data.
     *
     * @return Place
     */
    public function place()
    {
        return $this->belongsTo(Place::class);
    }

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
