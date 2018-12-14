<?php

namespace App\Services\Contact\Avatar;

use App\Services\BaseService;
use App\Models\Contact\Contact;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Helpers\AvatarHelper;

class GetAvatarsFromInternet extends BaseService
{
    protected $contact;

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

        $this->contact = Contact::findOrFail($data['contact_id']);

        $this->generateUUID();
        $this->getAdorable();
        $this->getGravatar();

        return $this->contact;
    }

    /**
     * Generate the UUID used to identify the contact in the Adorable service.
     *
     * @return void
     */
    private function generateUUID()
    {
        $this->contact->avatar_adorable_uuid = AvatarHelper::generateAdorableUUID();
        $this->contact->save();
    }

    /**
     * Get the adorable avatar.
     *
     * @return void
     */
    private function getAdorable()
    {
        $this->contact->avatar_adorable_url = (new GetAdorableAvatarURL)->execute([
            'uuid' => $this->contact->avatar_adorable_uuid,
            'size' => 200,
        ]);
        $this->contact->save();
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
                ->whereHas('contactFieldType', function ($query) {
                    $query->where('type', '=', 'email');
                })
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return;
        }

        return $contactField->data;
    }

    /**
     * Query Gravatar (if it exists) for the contact's email address.
     *
     * @return void
     */
    private function getGravatar()
    {
        $email = $this->getEmail($this->contact);

        if ($email) {
            $this->contact->avatar_gravatar_url = (new GetGravatarURL)->execute([
                'email' => $email,
                'size' => 200,
            ]);
        } else {
            // in this case we need to make sure that we reset the gravatar URL
            $this->contact->avatar_gravatar_url = null;

            if ($this->contact->avatar_source == 'gravatar') {
                $this->contact->avatar_source = 'adorable';
            }
        }

        $this->contact->save();
    }
}
