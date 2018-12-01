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


namespace App\Http\Controllers\Contacts;

use App\Models\Contact\Contact;
use App\Http\Controllers\Controller;
use App\Models\Contact\ContactField;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\People\ContactFieldsRequest;

class ContactFieldsController extends Controller
{
    /**
     * Get all the contact information for this contact.
     */
    public function getContactFields(Contact $contact)
    {
        $contactInformationData = collect([]);

        foreach ($contact->contactFields as $contactField) {
            $data = [
                'id' => $contactField->id,
                'data' => $contactField->data,
                'name' => $contactField->contactFieldType->name,
                'fontawesome_icon' => (is_null($contactField->contactFieldType->fontawesome_icon) ? null : $contactField->contactFieldType->fontawesome_icon),
                'protocol' =>  (is_null($contactField->contactFieldType->protocol) ? null : $contactField->contactFieldType->protocol),
                'contact_field_type_id' => $contactField->contact_field_type_id,
                'edit' => false,
            ];
            $contactInformationData->push($data);
        }

        return $contactInformationData;
    }

    /**
     * Get all the contact field types.
     * @param  Contact $contact
     */
    public function getContactFieldTypes(Contact $contact)
    {
        return auth()->user()->account->contactFieldTypes;
    }

    /**
     * Store the contact field.
     */
    public function storeContactField(ContactFieldsRequest $request, Contact $contact)
    {
        $contactField = $contact->contactFields()->create(
            $request->only([
                'contact_field_type_id',
                'data',
            ])
            + [
                'account_id' => auth()->user()->account_id,
            ]
        );

        $contact->updateGravatar();

        return $contactField;
    }

    /**
     * Edit the contact field.
     */
    public function editContactField(ContactFieldsRequest $request, Contact $contact, ContactField $contactField)
    {
        $contactField->update(
            $request->only([
                'contact_field_type_id',
                'data',
            ])
            + [
                'account_id' => auth()->user()->account_id,
            ]
        );

        $contact->updateGravatar();

        return $contactField;
    }

    public function destroyContactField(Contact $contact, ContactField $contactField)
    {
        $contactField->delete();

        $contact->updateGravatar();
    }
}
