<?php

namespace App\Services\Contact\Avatar;

use Illuminate\Support\Str;
use App\Services\BaseService;
use App\Models\Contact\Contact;
use Laravolt\Avatar\Facade as Avatar;
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
        $contact = $this->deleteExistingDefaultAvatar($contact);

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
            $contact->uuid = Str::uuid()->toString();
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
        $img = null;
        try {
            $img = Avatar::create($contact->name)
                ->setBackground($contact->default_avatar_color)
                ->getImageObject()
                ->encode('jpg');

            $filename = 'avatars/'.$contact->uuid.'.jpg';
            Storage::disk(config('filesystems.default'))
                ->put($filename, $img, 'public');

            // This will force the browser to reload the new avatar
            return $filename.'?'.now()->format('U');
        } finally {
            if ($img) {
                $img->destroy();
            }
        }
    }

    /**
     * Delete the existing default avatar.
     *
     * @param Contact  $contact
     * @return Contact
     */
    private function deleteExistingDefaultAvatar(Contact $contact)
    {
        try {
            Storage::disk(config('filesystems.default'))
                ->delete($contact->avatar_default_url);
            $contact->avatar_default_url = null;
        } catch (FileNotFoundException $e) {
            // ignore
        }

        return $contact;
    }
}
