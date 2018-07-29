<?php

namespace App\Http\Controllers\Contacts;

use App\Helpers\LocaleHelper;
use App\Models\Contact\Address;
use App\Models\Contact\Contact;
use App\Helpers\CountriesHelper;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use App\Http\Requests\People\AddressesRequest;

class AddressesController extends Controller
{
    /**
     * Get all the addresses for this contact.
     */
    public function get(Contact $contact)
    {
        $contactAddresses = collect([]);

        foreach ($contact->addresses as $address) {
            $data = [
                'id' => $address->id,
                'name' => $address->name,
                'googleMapAddress' => $address->getGoogleMapAddress(),
                'address' => $address->getFullAddress(),
                'country' => $address->country,
                'country_name' => $address->country_name,
                'street' => $address->street,
                'city' => $address->city,
                'province' => $address->province,
                'postal_code' => $address->postal_code,
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
    public function store(AddressesRequest $request, Contact $contact)
    {
        return $contact->addresses()->create([
            'account_id' => auth()->user()->account_id,
            'country' => ($request->input('country') == '0' ? null : $request->input('country')),
            'name' => ($request->input('name') == '' ? null : $request->input('name')),
            'street' => ($request->input('street') == '' ? null : $request->input('street')),
            'city' => ($request->input('city') == '' ? null : $request->input('city')),
            'province' => ($request->input('province') == '' ? null : $request->input('province')),
            'postal_code' => ($request->input('postal_code') == '' ? null : $request->input('postal_code')),
        ]);
    }

    /**
     * Edit the contact field.
     */
    public function edit(AddressesRequest $request, Contact $contact, Address $address)
    {
        $address->update([
            'country' => ($request->input('country') == '' ? null : $request->input('country')),
            'name' => ($request->input('name') == '' ? null : $request->input('name')),
            'street' => ($request->input('street') == '' ? null : $request->input('street')),
            'city' => ($request->input('city') == '' ? null : $request->input('city')),
            'province' => ($request->input('province') == '' ? null : $request->input('province')),
            'postal_code' => ($request->input('postal_code') == '' ? null : $request->input('postal_code')),
        ]);

        return $address;
    }

    public function destroy(Contact $contact, Address $address)
    {
        $address->delete();
    }
}
