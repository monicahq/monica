<?php

namespace App\Services\Contact\Avatar;

use App\Services\BaseService;
use App\Models\Contact\Contact;

class GetAvatarsFromInternet extends BaseService
{
    /**
     * Get the validation rules that apply to the service.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'contact_id' => 'required|integer|exists:contacts,id',
        ];
    }

    /**
     * Query both Gravatar and Adorable Avatars based on the email address of
     * the contact.
     *
     * - http://avatars.adorable.io/ gives avatars based on a random string.
     * This random string comes from the `avatar_adorable_uuid` field in the
     * Contact object.
     * - Gravatar only gives an avatar only if it's set.
     *
     * @param array $data
     * @return Contact
     */
    public function execute(array $data): Contact
    {
        $this->validate($data);

        $contact = Contact::findOrFail($data['contact_id']);

        $contact->avatar_gravatar_url = (new GetGravatar)->execute([
            'email' => $this->getEmail($contact),
            'size' => 200,
        ]);

        $contact->avatar_adorable_url = (new GetAdorableAvatar)->execute([
            'uuid' => $contact->avatar_adorable_uuid,
            'size' => 200,
        ]);

        $contact->save();

        return $contact;
    }

    private function getEmail(Contact $contact)
    {
        $contactField = $contact->contactFields()
            ->whereHas('contactFieldType', function ($query) {
                $query->where('type', '=', 'email');
            })
            ->first();

        return $contactField->data;
    }
}
