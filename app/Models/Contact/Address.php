<?php
/**
  *  This file is part of Monica.
  *
  *  Monica is free software: you can redistribute it and/or modify
  *  it under the terms of the GNU Affero General Public License as published by
  *  the Free Software Foundation, either version 3 of the License, or
  *  (at your option) any later version.
  *
  *  Monica is distributed in the hope that it will be useful,
  *  but WITHOUT ANY WARRANTY; without even the implied warranty of
  *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  *  GNU Affero General Public License for more details.
  *
  *  You should have received a copy of the GNU Affero General Public License
  *  along with Monica.  If not, see <https://www.gnu.org/licenses/>.
  **/


namespace App\Models\Contact;

use App\Models\Account\Account;
use App\Helpers\CountriesHelper;
use App\Models\ModelBindingWithContact as Model;
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
     * Get the account record associated with the address.
     *
     * @return BelongsTo
     */
    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the contact record associated with the address.
     *
     * @return BelongsTo
     */
    public function contact()
    {
        return $this->belongsTo(Contact::class);
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

        if (! is_null($this->country)) {
            $address .= ' '.$this->getCountryName();
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
            return CountriesHelper::get($this->country);
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

        return "https://www.google.com/maps/place/{$address}";
    }
}
