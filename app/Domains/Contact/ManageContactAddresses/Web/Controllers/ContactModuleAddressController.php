<?php

namespace App\Domains\Contact\ManageContactAddresses\Web\Controllers;

use App\Domains\Contact\ManageContactAddresses\Services\AssociateAddressToContact;
use App\Domains\Contact\ManageContactAddresses\Services\RemoveAddressFromContact;
use App\Domains\Contact\ManageContactAddresses\Web\ViewHelpers\ModuleContactAddressesViewHelper;
use App\Domains\Vault\ManageAddresses\Services\CreateAddress;
use App\Domains\Vault\ManageAddresses\Services\UpdateAddress;
use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ContactModuleAddressController extends Controller
{
    public function store(Request $request, string $vaultId, string $contactId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'vault_id' => $vaultId,
            'address_type_id' => $request->input('address_type_id') == 0 ? null : $request->input('address_type_id'),
            'line_1' => $request->input('line_1'),
            'line_2' => $request->input('line_2'),
            'city' => $request->input('city'),
            'province' => $request->input('province'),
            'postal_code' => $request->input('postal_code'),
            'country' => $request->input('country'),
            'latitude' => null,
            'longitude' => null,
        ];

        if (! $request->input('existing_address')) {
            $address = (new CreateAddress)->execute($data);
        } else {
            $address = Address::where('vault_id', $vaultId)
                ->findOrFail($request->input('existing_address_id'));
        }

        (new AssociateAddressToContact)->execute([
            'account_id' => Auth::user()->account_id,
            'vault_id' => $vaultId,
            'author_id' => Auth::id(),
            'contact_id' => $contactId,
            'address_id' => $address->id,
            'is_past_address' => $request->input('is_past_address'),
        ]);

        $contact = Contact::find($contactId);

        return response()->json([
            'data' => ModuleContactAddressesViewHelper::dto($contact, $address, Auth::user()),
        ], 201);
    }

    public function update(Request $request, string $vaultId, string $contactId, int $addressId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'vault_id' => $vaultId,
            'address_id' => $addressId,
            'address_type_id' => $request->input('address_type_id') == 0 ? null : $request->input('address_type_id'),
            'line_1' => $request->input('line_1'),
            'line_2' => $request->input('line_2'),
            'city' => $request->input('city'),
            'province' => $request->input('province'),
            'postal_code' => $request->input('postal_code'),
            'country' => $request->input('country'),
            'latitude' => null,
            'longitude' => null,
        ];

        (new UpdateAddress)->execute($data);
        $contact = Contact::find($contactId);

        // update pivot table
        $contact->addresses()->updateExistingPivot($addressId, [
            'is_past_address' => $request->input('is_past_address'),
        ]);

        // get the address with pivot information
        $address = $contact->addresses()->where('address_id', $addressId)->first();

        return response()->json([
            'data' => ModuleContactAddressesViewHelper::dto($contact, $address, Auth::user()),
        ], 200);
    }

    public function destroy(Request $request, string $vaultId, string $contactId, int $addressId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'vault_id' => $vaultId,
            'contact_id' => $contactId,
            'address_id' => $addressId,
        ];

        (new RemoveAddressFromContact)->execute($data);

        return response()->json([
            'data' => true,
        ], 200);
    }
}
