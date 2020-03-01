<?php

namespace App\Services\Contact\Avatar;

use Illuminate\Support\Str;
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

        $contact = $this->getAdorable($contact);
        $contact = $this->getGravatar($contact);

        return $contact;
    }

    /**
     * Generate the UUID used to identify the contact in the Adorable service.
     *
     * @param Contact  $contact
     * @return Contact
     */
    private function generateUUID(Contact $contact)
    {
        if (empty($contact->avatar_adorable_uuid)) {
            $contact->avatar_adorable_uuid = Str::uuid()->toString();
            $contact->save();
        }

        return $contact;
    }

    /**
     * Get the adorable avatar.
     *
     * @param Contact  $contact
     * @return Contact
     */
    private function getAdorable(Contact $contact)
    {
        // prevent timestamp update
        $timestamps = $contact->timestamps;
        $contact->timestamps = false;

        $contact = $this->generateUUID($contact);
        $contact->avatar_adorable_url = app(GetAdorableAvatarURL::class)->execute([
            'uuid' => $contact->avatar_adorable_uuid,
            'size' => 200,
        ]);
        $contact->save();

        $contact->timestamps = $timestamps;

        return $contact;
    }

    /**
     * Query Gravatar (if it exists) for the contact's email address.
     *
     * @param Contact  $contact
     * @return Contact
     */
    private function getGravatar(Contact $contact)
    {
        return app(GetGravatar::class)->execute([
            'contact_id' => $contact->id,
        ]);
    }
}
