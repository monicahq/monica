<?php

namespace App\Services\Contact\Contact;

use App\Services\BaseService;
use App\Models\Contact\Contact;
use Illuminate\Validation\Rule;
use App\Services\Contact\Avatar\GetAvatarsFromInternet;
use App\Services\Contact\Avatar\GenerateDefaultAvatar;
use App\Helpers\AvatarHelper;

class CreateContact extends BaseService
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
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'nickname' => 'nullable|string|max:255',
            'gender_id' => 'required|integer|exists:genders,id',
            'description' => 'nullable|string|max:255',
            'is_partial' => 'nullable|boolean',
        ];
    }

    /**
     * Create a contact.
     *
     * @param array $data
     * @return Contact
     */
    public function execute(array $data) : Contact
    {
        $this->validate($data);

        $this->contact = Contact::create($data);

        $this->generateUUID();

        $this->addAvatars();

        return $this->contact;
    }

    /**
     * Generates a UUID for this contact.
     *
     * @return void
     */
    private function generateUUID()
    {
        $this->contact->uuid = AvatarHelper::generateAdorableUUID();
        $this->contact->save();
    }

    /**
     * Add the different default avatars.
     *
     * @return void
     */
    private function addAvatars()
    {
        // set the default avatar color
        $this->contact->setAvatarColor();
        $this->contact->save();

        // populate the avatar from Adorable and grab the Gravatar
        $this->contact = (new GetAvatarsFromInternet)->execute([
            'contact_id' => $this->contact->id,
        ]);

        // also generate the default avatar
        $this->contact = (new GenerateDefaultAvatar)->execute([
            'contact_id' => $this->contact->id,
        ]);
    }
}
