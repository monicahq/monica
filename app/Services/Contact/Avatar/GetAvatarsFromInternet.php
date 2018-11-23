<?php

namespace App\Services\Contact\Avatar;

use App\Services\BaseService;
use App\Models\Contact\Contact;
use Illuminate\Database\Eloquent\ModelNotFoundException;

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

        $this->getGravatar($contact);

        $contact->avatar_adorable_url = (new GetAdorableAvatar)->execute([
            'uuid' => $this->generateRandomString($contact->id),
            'size' => 200,
        ]);

        $contact->save();

        return $contact;
    }

    private function getEmail(Contact $contact)
    {
        try {
            $contactField = $contact->contactFields()
                ->whereHas('contactFieldType', function ($query) {
                    $query->where('type', '=', 'email');
                })
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return;
        }

        return $contactField->data;
    }

    private function getGravatar(Contact $contact)
    {
        $email = $this->getEmail($contact);

        if ($email) {
            $contact->avatar_gravatar_url = (new GetGravatar)->execute([
                'email' => $email,
                'size' => 200,
            ]);
        } else {
            // in this case we need to make sure that we reset the gravatar URL
            $contact->avatar_gravatar_url = null;

            if ($contact->avatar_source == 'gravatar') {
                $contact->avatar_source = 'adorable';
            }
        }
    }

    /**
     * Generate a random string to pass to Adorable.
     *
     * @param string $string
     * @return string
     */
    private function generateRandomString($string)
    {
        // bcrypt can insert '/' so we need to remove them
        return str_replace('/', '', bcrypt($string));
    }
}
