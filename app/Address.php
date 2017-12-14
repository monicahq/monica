<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Address extends Model
{
    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    protected $table = 'addresses';

    /**
     * Get the account record associated with the gift.
     *
     * @return BelongsTo
     */
    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the contact record associated with the gift.
     *
     * @return BelongsTo
     */
    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    /**
     * Get the Country records associated with the contact.
     *
     * @return BelongsTo
     */
    public function country()
    {
        return $this->belongsTo('App\Country');
    }

    /**
     * Get the address in a format like 'Lives in Scranton, MS'.
     *
     * @return string
     */
    public function getPartialAddress()
    {
        $address = $this->city;

        if (is_null($address)) {
            return;
        }

        if (! is_null($this->province)) {
            $address = $address.', '.$this->province;
        }

        return $address;
    }

    /**
     * Get the address in a format like 'Lives in Scranton, MS'.
     *
     * @return string
     */
    public function getFullAddress()
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

        if (! is_null($this->country_id)) {
            $address .= ' '.$this->country->country;
        }

        if (is_null($address)) {
            return;
        }

        // trim extra whitespaces inside the address
        $address = preg_replace('/\s+/', ' ', $address);

        return $address;
    }

    /**
     * Get the country of the contact.
     *
     * @return string or null
     */
    public function getCountryName()
    {
        if ($this->country) {
            return $this->country->country;
        }
    }

    /**
     * Get the country ISO of the contact.
     *
     * @return string or null
     */
    public function getCountryISO()
    {
        if ($this->country) {
            return $this->country->iso;
        }
    }

    /**
     * Get an URL for Google Maps for the address.
     *
     * @return string
     */
    public function getGoogleMapAddress()
    {
        $address = $this->getFullAddress();
        $address = urlencode($address);

        return "https://www.google.ca/maps/place/{$address}";
    }
}
