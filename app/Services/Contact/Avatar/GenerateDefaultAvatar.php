<?php

namespace App\Services\Contact\Avatar;

use Laravolt\Avatar\Avatar;
use App\Helpers\RandomHelper;
use App\Services\BaseService;
use App\Models\Contact\Contact;
use Illuminate\Support\Facades\Storage;
use Illuminate\Contracts\Filesystem\FileNotFoundException;

class GenerateDefaultAvatar extends BaseService
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
     * Generate the default image for the avatar, based on the initals of the
     * contact and returns the filename.
     *
     * @param array $data
     * @return Contact
     */
    public function execute(array $data)
    {
        $this->validate($data);

        $contact = Contact::find($data['contact_id']);

        $contact = $this->generateContactUUID($contact);

        // delete existing default avatar
        $this->deleteExistingDefaultAvatar($contact);

        // create new avatar
        $filename = $this->createNewAvatar($contact);

        $contact->avatar_default_url = $filename;
        $contact->save();

        return $contact;
    }

    /**
     * Create an uuid for the contact if it does not exist.
     *
     * @param Contact  $contact
     * @return Contact
     */
    private function generateContactUUID(Contact $contact)
    {
        if (! $contact->uuid) {
            $contact->uuid = RandomHelper::uuid();
            $contact->save();
        }

        return $contact;
    }

    /**
     * Create a new avatar for the contact based on the name of the contact.
     *
     * @param Contact  $contact
     * @return string
     */
    private function createNewAvatar(Contact $contact)
    {
        $img = (new Avatar([
            'width' => '150',
            'height' => '150',
            'shape' => 'square',
            'backgrounds' => [$contact->default_avatar_color],
            'ascii' => true,
        ]))->create($contact->name)->getImageObject()->encode('jpg');

        $filename = 'avatars/'.$contact->uuid.'.jpg';
        Storage::put($filename, $img);

        return $filename;
    }

    /**
     * Delete the existing default avatar.
     *
     * @param Contact  $contact
     * @return void
     */
    private function deleteExistingDefaultAvatar(Contact $contact)
    {
        try {
            Storage::delete($contact->avatar_default_url);
            $contact->avatar_default_url = null;
            $contact->save();
        } catch (FileNotFoundException $e) {
            return;
        }
    }
}
