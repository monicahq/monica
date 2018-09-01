<?php

/**
 * This is a single action class, totally inspired by
 * https://medium.com/@remi_collin/keeping-your-laravel-applications-dry-with-single-action-classes-6a950ec54d1d.
 */

namespace App\Services\Contact;

use App\Services\BaseService;
use App\Models\Contact\Contact;
use Illuminate\Support\Facades\Storage;
use Illuminate\Contracts\Filesystem\FileNotFoundException;

/**
 * Delete the avatars files of a contact.
 */
class DeleteAvatars extends BaseService
{
    /**
     * The structure that the method expects to receive as parameter.
     *
     * @var array
     */
    private $structure = [
        'contact',
    ];

    /**
     * Execute the service.
     *
     * @param array $data
     * @return bool
     */
    public function execute(array $data) : bool
    {
        if (! $this->validateDataStructure($data, $this->structure)) {
            throw new MissingParameterException('Missing parameters');
        }

        $contact = $data['contact'];

        if ($contact->avatar_location != 'external') {
            $this->deleteAvatarSize($contact);
            $this->deleteAvatarSize($contact, 110);
            $this->deleteAvatarSize($contact, 174);
        }

        return true;
    }

    /**
     * Delete avatar file for one size.
     */
    private function deleteAvatarSize($contact, $size = null)
    {
        $avatarFileName = $contact->avatar_file_name;
        if (! is_null($size)) {
            $filename = pathinfo($contact->avatar_file_name, PATHINFO_FILENAME);
            $extension = pathinfo($contact->avatar_file_name, PATHINFO_EXTENSION);
            $avatarFileName = 'avatars/'.$filename.'_'.$size.'.'.$extension;
        }

        try {
            $storage = Storage::disk($contact->avatar_location);
            if ($storage->exists($avatarFileName)) {
                $storage->delete($avatarFileName);
            }
        } catch (FileNotFoundException $e) {
            return;
        }
    }
}
