<?php

namespace App\Models\Account;

use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\App;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Weather extends Model
{
    protected $table = 'weather';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'account_id',
        'place_id',
        'weather_json',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'weather_json' => 'array',
    ];

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array<string>|bool
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
     * @return BelongsTo
     */
    public function place()
    {
        return $this->belongsTo(Place::class);
    }

    /**
     * Get the weather code.
     *
     * @return string|null
     */
    public function getSummaryCodeAttribute(): ?string
    {
        $json = $this->weather_json;

        // currently.icon: Darksky version
        if (! ($icon = Arr::get($json, 'currently.icon'))) {
            if (($text = Arr::get($json, 'current.condition.text')) === 'Partly cloudy') {
                $icon = ((bool) Arr::get($json, 'current.is_day')) ? 'partly-cloudy-day' : 'partly-cloudy-night';
            } else {
                $icon = (string) Str::of($text)->lower()->replace(' ', '-');
            }
        }

        return $icon;
    }

    /**
     * Get the weather summary.
     *
     * @return string|null
     */
    public function getSummaryAttribute(): ?string
    {
        $summary_code = $this->summary_code;
        if (empty($summary_code)) {
            return null;
        }

        return (string) Str::of(trans('app.weather_'.$summary_code));
    }

    /**
     * Get the weather location.
     *
     * @return string|null
     */
    public function getLocationAttribute(): ?string
    {
        return Arr::get($this->weather_json, 'location.name');
    }

    /**
     * Get the weather update date.
     *
     * @return Carbon
     */
    public function getDateAttribute(): ?Carbon
    {
        if (($timestamp = Arr::get($this->weather_json, 'current.last_updated_epoch')) !== null) {
            return Carbon::createFromTimestamp($timestamp);
        }

        return null;
    }

    /**
     * Get the weather icon.
     *
     * @return string
     *
     * @codeCoverageIgnore
     */
    public function getEmojiAttribute(): string
    {
        switch ($this->summary_code) {
            case 'sunny':
            case 'clear-day':
                $string = '🌞';
                break;
            case 'clear':
            case 'clear-night':
                $string = '🌃';
                break;
            case 'light-drizzle':
            case 'patchy-light-drizzle':
            case 'patchy-light-rain':
            case 'light-rain':
            case 'moderate-rain-at-times':
            case 'moderate-rain':
            case 'patchy-rain-possible':
            case 'heavy-rain-at-times':
            case 'heavy-rain':
            case 'light-freezing-rain':
            case 'moderate-or-heavy-freezing-rain':
            case 'light-sleet':
            case 'moderate-or-heavy-rain-shower':
            case 'light-rain-shower':
            case 'torrential-rain-shower':
            case 'rain':
                $string = '🌧️';
                break;
            case 'snow':
            case 'blowing-snow':
            case 'patchy-light-snow':
            case 'light-snow':
            case 'patchy-moderate-snow':
            case 'moderate-snow':
            case 'patchy-heavy-snow':
            case 'heavy-snow':
            case 'light-snow-showers':
            case 'moderate-or-heavy-snow-showers':
                $string = '❄️';
                break;
            case 'patchy-snow-possible':
            case 'patchy-sleet-possible':
            case 'moderate-or-heavy-sleet':
            case 'light-sleet-showers':
            case 'moderate-or-heavy-sleet-showers':
            case 'sleet':
                $string = '🌨️';
                break;
            case 'wind':
                $string = '💨';
                break;
            case 'fog':
            case 'mist':
            case 'blizzard':
            case 'freezing-fog':
                $string = '🌫️';
                break;
            case 'overcast':
            case 'cloudy':
                $string = '☁️';
                break;
            case 'partly-cloudy-day':
                $string = '⛅';
                break;
            case 'partly-cloudy-night':
                $string = '🎑';
                break;
            case 'freezing-drizzle':
            case 'heavy-freezing-drizzle':
            case 'patchy-freezing-drizzle-possible':
            case 'ice-pellets':
            case 'light-showers-of-ice-pellets':
            case 'moderate-or-heavy-showers-of-ice-pellets':
                $string = '🧊';
                break;
            case 'thundery-outbreaks-possible':
            case 'patchy-light-rain-with-thunder':
            case 'moderate-or-heavy-rain-with-thunder':
            case 'patchy-light-snow-with-thunder':
            case 'moderate-or-heavy-snow-with-thunder':
                $string = '⛈️';
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
     * @param  string  $scale
     * @return string
     */
    public function temperature($scale = 'celsius')
    {
        $json = $this->weather_json;

        $temperature = Arr::get($json, 'currently.temperature') ?? Arr::get($json, 'current.temp_c');

        if ($scale === 'fahrenheit') {
            $temperature = Arr::get($json, 'current.temp_f', 9 / 5 * $temperature + 32);
        }

        $temperature = round($temperature, 1);

        $numberFormatter = new \NumberFormatter(App::getLocale(), \NumberFormatter::DECIMAL);

        return $numberFormatter->format($temperature);
    }
}
