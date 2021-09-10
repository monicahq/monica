<?php

namespace App\Services\Contact\Tag;

use App\Models\Contact\Tag;
use App\Services\BaseService;
use App\Models\Contact\Contact;

class DetachTag extends BaseService
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
            'contact_id' => 'required|integer',
            'tag_id' => 'required|integer',
        ];
    }

    /**
     * Detach the tag associated with a contact.
     *
     * @param  array  $data
     * @return void
     */
    public function execute(array $data)
    {
        $this->validate($data);

        $contact = Contact::where('account_id', $data['account_id'])
                            ->findOrFail($data['contact_id']);

        $contact->throwInactive();

        Tag::where('account_id', $data['account_id'])
            ->findOrFail($data['tag_id']);

        $contact->tags()->detach($data['tag_id']);
    }
}
