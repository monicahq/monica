<?php

namespace App\Http\Controllers\People;

use Auth;
use App\Address;
use App\Contact;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\People\ContactFieldsRequest;

class AddressesController extends Controller
{
    /**
     * Get all the addresses for this contact
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
                'edit' => false,
            ];
            $contactAddresses->push($data);
        }

        return $contactAddresses;
    }

    /**
     * Get all the contact field types
     * @param  Contact $contact
     */
    public function getContactFieldTypes(Contact $contact)
    {
        return auth()->user()->account->contactFieldTypes;
    }

    /**
     * Store the contact field
     */
    public function storeContactField(ContactFieldsRequest $request, Contact $contact)
    {
        $contactField = $contact->contactFields()->create(
            $request->only([
                'contact_field_type_id',
                'data',
            ])
            + [
                'account_id' => auth()->user()->account->id,
            ]
        );

        return $contactField;
    }
    /**
     * Edit the contact field
     */
    public function editContactField(ContactFieldsRequest $request, Contact $contact, ContactField $contactField)
    {
        $contactField->update(
            $request->only([
                'contact_field_type_id',
                'data',
            ])
            + [
                'account_id' => auth()->user()->account->id,
            ]
        );

        return $contactField;
    }

    public function destroyContactField(Contact $contact, ContactField $contactField)
    {
        $contactField->delete();
    }
}
