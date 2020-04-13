<?php

namespace App\Models\Account;

use App\Models\Contact\Contact;
use function Safe\preg_replace;
use App\Helpers\CountriesHelper;
use App\Models\ModelBinding as Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A Place is not the same as an address.
 * An Address in Monica is a way of contacting the contact. An Address is linked
 * to a Place. But Places can exist without the Address object.
 * Places will be linked to activities, for instance.
 */
class Place extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'places';
    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    /**
     * Get the account record associated with the place.
     *
     * @return BelongsTo
     */
    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the Weather record associated with the place.
     *
     * @return HasMany
     */
    public function weathers()
    {
        return $this->hasMany(Weather::class);
    }

    /**
     * Get the address as a sentence.
     *
     * @return string|null
     */
    public function getAddressAsString(): ?string
    {
        $address = '';

        if (! is_null($this->street)) {
            $address = $this->street;
        }

        if (! is_null($this->city)) {
            $address .= ' '.$this->city;
        }

        if (! is_null($this->province)) {
            $address .= ' '.$this->province;
        }

        if (! is_null($this->postal_code)) {
            $address .= ' '.$this->postal_code;
        }

        if (! is_null($this->country)) {
            $address .= ' '.$this->getCountryName();
        }

        if (empty($address)) {
            return null;
        }

        // trim extra whitespaces inside the address
        $address = preg_replace('/\s+/', ' ', $address);
        if (is_string($address)) {
            return $address;
        }

        return null;
    }

    /**
     * Get the country of the place.
     *
     * @return string|null
     */
    public function getCountryName(): ?string
    {
        if ($this->country) {
            return CountriesHelper::get($this->country);
        }

        return null;
    }

    /**
     * Get an URL for Google Maps for the place.
     *
     * @return string
     */
    public function getGoogleMapAddress()
    {
        $place = $this->getAddressAsString();
        $place = urlencode($place);

        return "https://www.google.com/maps/place/{$place}";
    }

    /**
     * Get the Google Maps url for the latitude/longitude.
     *
     * @return string
     */
    public function getGoogleMapsAddressWithLatitude()
    {
        return 'http://maps.google.com/maps?q='.$this->latitude.','.+$this->longitude;
    }
}
