<?php

namespace App\Services\VCard;

use App\Services\BaseService;
use App\Models\Contact\Contact;

class GetEtag extends BaseService
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
        ];
    }

    /**
     * Export etag of the VCard.
     *
     * @param  array  $data
     * @return string
     */
    public function execute(array $data): string
    {
        $this->validate($data);

        $contact = Contact::where('account_id', $data['account_id'])
            ->findOrFail($data['contact_id']);

        return $contact->distant_etag ?? '"'.sha1($contact->vcard).'"';
    }
}
