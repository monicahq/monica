<?php

namespace App\Http\Controllers\Contacts;

use Auth;
use App\Address;
use App\Contact;
use App\Country;
use App\Http\Controllers\Controller;
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
                'country_id' => $address->country_id,
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
        return Country::orderBy('country')->get();
    }

    /**
     * Store the address.
     */
    public function store(AddressesRequest $request, Contact $contact)
    {
        return $contact->addresses()->create([
            'account_id' => auth()->user()->account->id,
            'country_id' => ($request->get('country_id') == 0 ? null : $request->get('country_id')),
            'name' => ($request->get('name') == '' ? null : $request->get('name')),
            'street' => ($request->get('street') == '' ? null : $request->get('street')),
            'city' => ($request->get('city') == '' ? null : $request->get('city')),
            'province' => ($request->get('province') == '' ? null : $request->get('province')),
            'postal_code' => ($request->get('postal_code') == '' ? null : $request->get('postal_code')),
        ]);
    }

    /**
     * Edit the contact field.
     */
    public function edit(AddressesRequest $request, Contact $contact, Address $address)
    {
        $address->update([
            'country_id' => ($request->get('country_id') == 0 ? null : $request->get('country_id')),
            'name' => ($request->get('name') == '' ? null : $request->get('name')),
            'street' => ($request->get('street') == '' ? null : $request->get('street')),
            'city' => ($request->get('city') == '' ? null : $request->get('city')),
            'province' => ($request->get('province') == '' ? null : $request->get('province')),
            'postal_code' => ($request->get('postal_code') == '' ? null : $request->get('postal_code')),
        ]);

        return $address;
    }

    public function destroy(Contact $contact, Address $address)
    {
        $address->delete();
    }
}
