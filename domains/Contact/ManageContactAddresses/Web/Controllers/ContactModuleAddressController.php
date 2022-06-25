<?php

namespace App\Contact\ManageContactAddresses\Web\Controllers;

use App\Contact\ManageContactAddresses\Services\CreateContactAddress;
use App\Contact\ManageContactAddresses\Services\DestroyContactAddress;
use App\Contact\ManageContactAddresses\Services\UpdateContactAddress;
use App\Contact\ManageContactAddresses\Web\ViewHelpers\ModuleContactAddressesViewHelper;
use App\Http\Controllers\Controller;
use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ContactModuleAddressController extends Controller
{
    public function store(Request $request, int $vaultId, int $contactId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::user()->id,
            'vault_id' => $vaultId,
            'contact_id' => $contactId,
            'address_type_id' => $request->input('address_type_id') == 0 ? null : $request->input('address_type_id'),
            'street' => $request->input('street'),
            'city' => $request->input('city'),
            'province' => $request->input('province'),
            'postal_code' => $request->input('postal_code'),
            'country' => $request->input('country'),
            'is_past_address' => $request->input('is_past_address'),
            'latitude' => null,
            'longitude' => null,
            'lived_from_at' => null,
            'lived_until_at' => null,
        ];

        $address = (new CreateContactAddress())->execute($data);
        $contact = Contact::find($contactId);

        return response()->json([
            'data' => ModuleContactAddressesViewHelper::dto($contact, $address, Auth::user()),
        ], 201);
    }

    public function update(Request $request, int $vaultId, int $contactId, int $addressId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::user()->id,
            'vault_id' => $vaultId,
            'contact_id' => $contactId,
            'address_id' => $addressId,
            'address_type_id' => $request->input('address_type_id') == 0 ? null : $request->input('address_type_id'),
            'street' => $request->input('street'),
            'city' => $request->input('city'),
            'province' => $request->input('province'),
            'postal_code' => $request->input('postal_code'),
            'country' => $request->input('country'),
            'is_past_address' => $request->input('is_past_address'),
            'latitude' => null,
            'longitude' => null,
            'lived_from_at' => null,
            'lived_until_at' => null,
        ];

        $address = (new UpdateContactAddress())->execute($data);
        $contact = Contact::find($contactId);

        return response()->json([
            'data' => ModuleContactAddressesViewHelper::dto($contact, $address, Auth::user()),
        ], 200);
    }

    public function destroy(Request $request, int $vaultId, int $contactId, int $addressId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::user()->id,
            'vault_id' => $vaultId,
            'contact_id' => $contactId,
            'address_id' => $addressId,
        ];

        (new DestroyContactAddress())->execute($data);

        return response()->json([
            'data' => true,
        ], 200);
    }
}
