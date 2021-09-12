<?php

namespace App\Http\Controllers\Contacts;

use Illuminate\Http\Request;
use App\Models\Contact\Address;
use App\Models\Contact\Contact;
use App\Helpers\CountriesHelper;
use Illuminate\Support\Facades\App;
use App\Http\Controllers\Controller;
use App\Traits\JsonRespondController;
use Illuminate\Support\Facades\Cache;
use App\Services\Contact\Address\CreateAddress;
use App\Services\Contact\Address\UpdateAddress;
use App\Services\Contact\Address\DestroyAddress;

class AddressesController extends Controller
{
    use JsonRespondController;

    /**
     * Get all the addresses for this contact.
     */
    public function index(Contact $contact)
    {
        $addresses = collect([]);

        foreach ($contact->addresses as $address) {
            $addresses->push($this->addressObject($address));
        }

        return $addresses;
    }

    /**
     * Get all the countries.
     */
    public function getCountries()
    {
        $key = 'countries.'.App::getLocale();

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
        $datas = [
            'account_id' => auth()->user()->account_id,
            'contact_id' => $contact->id,
        ] + $request->only([
            'name',
            'country',
            'street',
            'city',
            'province',
            'postal_code',
            'latitude',
            'longitude',
        ]);

        $address = app(CreateAddress::class)->execute($datas);

        return $this->setHTTPStatusCode(201)
                    ->respond($this->addressObject($address));
    }

    /**
     * Edit the contact field.
     */
    public function edit(Request $request, Contact $contact, Address $address)
    {
        $datas = [
            'account_id' => auth()->user()->account_id,
            'contact_id' => $contact->id,
            'address_id' => $address->id,
        ] + $request->only([
            'name',
            'country',
            'street',
            'city',
            'province',
            'postal_code',
            'latitude',
            'longitude',
        ]);

        $address = app(UpdateAddress::class)->execute($datas);

        return $this->respond($this->addressObject($address));
    }

    /**
     * Destroy the address.
     *
     * @param  Request  $request
     * @param  Contact  $contact
     * @param  Address  $address
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, Contact $contact, Address $address)
    {
        $datas = [
            'account_id' => auth()->user()->account_id,
            'address_id' => $address->id,
        ];

        if (app(DestroyAddress::class)->execute($datas)) {
            return $this->respondObjectDeleted($address->id);
        }

        return $this->setHTTPStatusCode(400)
                    ->setErrorCode(32)
                    ->respondWithError();
    }

    private function addressObject($address)
    {
        $place = $address->place;

        return [
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
    }
}
