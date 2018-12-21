<?php

namespace App\Http\Controllers\Contacts;

use Illuminate\Http\Request;
use App\Helpers\LocaleHelper;
use App\Models\Contact\Address;
use App\Models\Contact\Contact;
use App\Helpers\CountriesHelper;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use App\Services\Contact\Address\CreateAddress;
use App\Services\Contact\Address\UpdateAddress;
use App\Services\Contact\Address\DestroyAddress;

class AddressesController extends Controller
{
    /**
     * Get all the addresses for this contact.
     */
    public function index(Contact $contact)
    {
        $contactAddresses = collect([]);

        foreach ($contact->addresses as $address) {
            $place = $address->place;
            $data = [
                'id' => $address->id,
                'name' => $address->name,
                'googleMapAddress' => $place->getGoogleMapAddress(),
                'googleMapAddressLatitude' => $place->getGoogleMapsAddressWithLatitude(),
                'address' => $place->getAddressAsString(),
                'country' => $place->country,
                'country_name' => $place->country_name,
                'street' => $place->street,
                'city' => $place->city,
                'province' => $place->province,
                'postal_code' => $place->postal_code,
                'latitude' => $place->latitude,
                'longitude' => $place->longitude,
                'edit' => false,
            ];
            $contactAddresses->push($data);
        }

        return $contactAddresses;
    }

    /**
     * Get all the countries.
     */
    public function getCountries()
    {
        $key = 'countries.'.LocaleHelper::getLocale();

        $countries = Cache::rememberForever($key, function () {
            return CountriesHelper::getAll();
        });

        return response()->json($countries->all());
    }

    /**
     * Store the address.
     */
    public function store(Request $request, Contact $contact)
    {
        $request = [
            'account_id' => auth()->user()->account->id,
            'contact_id' => $contact->id,
            'name' => $request->get('name'),
            'country' => $request->get('country'),
            'street' => $request->get('street'),
            'city' => $request->get('city'),
            'province' => $request->get('province'),
            'postal_code' => $request->get('postal_code'),
            'latitude' => $request->get('latitude'),
            'longitude' => $request->get('longitude'),
        ];

        return (new CreateAddress)->execute($request);
    }

    /**
     * Edit the contact field.
     */
    public function edit(Request $request, Contact $contact, Address $address)
    {
        $request = [
            'account_id' => auth()->user()->account->id,
            'contact_id' => $contact->id,
            'address_id' => $address->id,
            'name' => $request->get('name'),
            'country' => $request->get('country'),
            'street' => $request->get('street'),
            'city' => $request->get('city'),
            'province' => $request->get('province'),
            'postal_code' => $request->get('postal_code'),
            'latitude' => $request->get('latitude'),
            'longitude' => $request->get('longitude'),
        ];

        return (new UpdateAddress)->execute($request);
    }

    /**
     * Destroy the address.
     *
     * @param Request $request
     * @param Contact $contact
     * @param Address $address
     * @return void
     */
    public function destroy(Request $request, Contact $contact, Address $address)
    {
        $request = [
            'account_id' => auth()->user()->account->id,
            'address_id' => $address->id,
        ];

        (new DestroyAddress)->execute($request);
    }
}
