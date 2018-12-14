<?php

namespace App\Services\Contact\Avatar;

use Laravolt\Avatar\Avatar;
use App\Helpers\AvatarHelper;
use App\Services\BaseService;
use App\Models\Contact\Contact;
use Illuminate\Support\Facades\Storage;
use Illuminate\Contracts\Filesystem\FileNotFoundException;

class GenerateDefaultAvatar extends BaseService
{
    private $contact;

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

        $this->contact = Contact::find($data['contact_id']);

        // delete existing default avatar
        $this->deleteExistingDefaultAvatar();

        // create new avatar
        $filename = $this->createNewAvatar();

        $this->contact->avatar_default_url = $filename;
        $this->contact->save();

        return $this->contact;
    }

    /**
     * Create a new avatar for the contact based on the name of the contact.
     *
     * @return void
     */
    private function createNewAvatar()
    {
        $img = (new Avatar([
            'width' => '150',
            'height' => '150',
            'shape' => 'square',
            'backgrounds' => [$this->contact->default_avatar_color],
            'ascii' => true,
        ]))->create($this->contact->name)->getImageObject()->encode('jpg');

        $filename = 'avatars/'.$this->contact->uuid.'.jpg';
        Storage::put($filename, $img);

        return $filename;
    }

    /**
     * Delete the existing default avatar.
     *
     * @return void
     */
    private function deleteExistingDefaultAvatar()
    {
        try {
            Storage::delete($this->contact->avatar_default_url);
            $this->contact->avatar_default_url = null;
            $this->contact->save();
        } catch (FileNotFoundException $e) {
            return;
        }
    }
}
