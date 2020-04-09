<?php

namespace App\Services\Contact\Avatar;

use App\Models\Account\Photo;
use App\Services\BaseService;
use App\Models\Contact\Contact;
use Illuminate\Validation\Rule;

/**
 * Update the avatar of the contact.
 */
class UpdateAvatar extends BaseService
{
    /**
     * Get the validation rules that apply to the service.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'account_id' => 'required|integer|exists:accounts,id',
            'contact_id' => 'required|integer|exists:contacts,id',
            'source' => [
                'required',
                Rule::in([
                    'default',
                    'adorable',
                    'gravatar',
                    'photo',
                ]),
            ],
            'photo_id' => 'required_if:source,photo|integer|exists:photos,id',
        ];
    }

    /**
     * Update message in a conversation.
     *
     * @param array $data
     * @return Contact
     */
    public function execute(array $data): Contact
    {
        $this->validate($data);

        $contact = Contact::where('account_id', $data['account_id'])
            ->findOrFail($data['contact_id']);

        if (isset($data['photo_id'])) {
            Photo::where('account_id', $data['account_id'])
                ->findOrFail($data['photo_id']);
        }

        $contact->avatar_source = $data['source'];
        switch ($contact->avatar_source) {
            case 'photo':
            // in case of a photo, set the photo as the avatar
                $contact->avatar_photo_id = $this->nullOrValue($data, 'photo_id');
                $contact->photos()->syncWithoutDetaching([$this->nullOrValue($data, 'photo_id')]);
                break;
            default:
                $contact->avatar_photo_id = null;
                break;
        }

        $contact->save();

        return $contact;
    }
}
