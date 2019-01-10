<?php

namespace App\Models\Account;

use Illuminate\Support\Facades\App;
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

    /**
     * Get the emoji representing the weather.
     *
     * @return string
     */
    public function getEmoji()
    {
        switch ($this->summary_icon) {
            case 'clear-day':
                $string = '☀️';
                break;
            case 'clear-night':
                $string = '🌌';
                break;
            case 'rain':
                $string = '🌧️';
                break;
            case 'snow':
                $string = '❄️';
                break;
            case 'sleet':
                $string = '🌨️';
                break;
            case 'wind':
                $string = '💨';
                break;
            case 'fog':
                $string = '🌫️';
                break;
            case 'cloudy':
                $string = '☁️';
                break;
            case 'partly-cloudy-day':
                $string = '⛅';
                break;
            case 'partly-cloudy-night':
                $string = '🎑';
                break;
            default:
                $string = '🌈';
                break;
        }

        return $string;
    }

    /**
     * Get the temperature attribute.
     * Temperature is fetched in Celsius. It needs to be
     * converted to Fahrenheit depending on the user.
     *
     * @return string
     */
    public function temperature($scale = 'celsius')
    {
        $json = $this->weather_json;

        $temperature = $json['currently']['temperature'];

        if ($scale == 'fahrenheit') {
            $temperature = 9 / 5 * $temperature + 32;
        }

        $temperature = round($temperature, 1);

        $numberFormatter = new \NumberFormatter(App::getLocale(), \NumberFormatter::DECIMAL);

        return $numberFormatter->format($temperature);
    }
}
