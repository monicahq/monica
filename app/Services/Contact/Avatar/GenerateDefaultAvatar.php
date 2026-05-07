<?php

namespace App\Services\Contact\Avatar;

use Illuminate\Support\Str;
use App\Services\BaseService;
use App\Models\Contact\Contact;
use Illuminate\Support\Facades\Cache;
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
     * @param  array  $data
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

        Cache::forget('etag'.Str::before('?', $filename));

        return $contact;
    }

    /**
     * Create an uuid for the contact if it does not exist.
     *
     * @param  Contact  $contact
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
     * Create a new SVG avatar for the contact based on the initials of the contact name.
     *
     * @param  Contact  $contact
     * @return string
     */
    private function createNewAvatar(Contact $contact): string
    {
        $initials = $this->getInitials($contact->name);

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100">'
            .'<rect width="100" height="100" fill="'.htmlspecialchars($contact->default_avatar_color, ENT_XML1, 'UTF-8').'"/>'
            .'<text x="50" y="50" text-anchor="middle" dominant-baseline="central" '
            .'font-family="sans-serif" font-size="40" fill="white" font-weight="bold">'
            .htmlspecialchars($initials, ENT_XML1, 'UTF-8')
            .'</text></svg>';

        $filename = 'avatars/'.$contact->uuid.'.svg';
        Storage::disk(config('filesystems.default'))
            ->put($filename, $svg, config('filesystems.default_visibility'));

        return $filename.'?'.now()->format('U');
    }

    /**
     * Extract up to two initials from a full name.
     *
     * @param  string  $name
     * @return string
     */
    private function getInitials(string $name): string
    {
        $words = preg_split('/\s+/', trim($name), -1, PREG_SPLIT_NO_EMPTY);
        if (empty($words)) {
            return '?';
        }
        $initials = mb_strtoupper(mb_substr($words[0], 0, 1));
        if (count($words) > 1) {
            $initials .= mb_strtoupper(mb_substr(end($words), 0, 1));
        }

        return $initials;
    }

    /**
     * Delete the existing default avatar.
     *
     * @param  Contact  $contact
     * @return Contact
     */
    private function deleteExistingDefaultAvatar(Contact $contact)
    {
        if ($contact->avatar_default_url !== null) {
            try {
                Storage::disk(config('filesystems.default'))
                    ->delete($contact->avatar_default_url);
                $contact->avatar_default_url = null;
            } catch (FileNotFoundException $e) {
                // ignore
            }
        }

        return $contact;
    }
}
