<?php

namespace App\Services\Contact\Avatar;

use Illuminate\Support\Str;
use App\Helpers\StringHelper;
use App\Services\BaseService;
use App\Models\Contact\Contact;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
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

        $contact = $this->generateUUID($contact);
        $contact = $this->getAdorable($contact);
        $contact = $this->getGravatar($contact);
        $contact->save();

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
        $contact->avatar_adorable_url = app(GetAdorableAvatarURL::class)->execute([
            'uuid' => $contact->avatar_adorable_uuid,
            'size' => 200,
        ]);

        return $contact;
    }

    /**
     * Get the email (if it exists) of the contact, based on the contact fields.
     *
     * @param Contact $contact
     * @return null|string
     */
    private function getEmail(Contact $contact)
    {
        try {
            $contactField = $contact->contactFields()
                                    ->email()
                                    ->first();

            $email = $contactField ? $contactField->data : null;

            Validator::make(['email' => $email], ['email' => 'email'])
                ->validate();

            return $email;
        } catch (ModelNotFoundException $e) {
            // Not found
        } catch (ValidationException $e) {
            // Not an email
        }
    }

    /**
     * Query Gravatar (if it exists) for the contact's email address.
     *
     * @param Contact  $contact
     * @return Contact
     */
    private function getGravatar(Contact $contact)
    {
        $email = $this->getEmail($contact);

        if (! StringHelper::isNullOrWhitespace($email)) {
            $contact->avatar_gravatar_url = app(GetGravatarURL::class)->execute([
                'email' => $email,
                'size' => config('monica.avatar_size'),
            ]);
        } else {
            // in this case we need to make sure that we reset the gravatar URL
            $contact->avatar_gravatar_url = null;

            if ($contact->avatar_source == 'gravatar') {
                $contact->avatar_source = 'adorable';
            }
        }

        return $contact;
    }
}
