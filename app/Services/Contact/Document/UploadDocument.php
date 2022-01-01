<?php

namespace App\Services\Contact\Document;

use App\Services\BaseService;
use App\Helpers\AccountHelper;
use App\Models\Account\Account;
use App\Models\Contact\Contact;
use App\Models\Contact\Document;

class UploadDocument extends BaseService
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
            'document' => 'required|file',
        ];
    }

    /**
     * Upload a document.
     *
     * @param  array  $data
     * @return Document
     */
    public function execute(array $data): Document
    {
        $this->validate($data);

        $account = Account::find($data['account_id']);
        if (AccountHelper::hasLimitations($account)) {
            abort(402);
        }

        $contact = Contact::where('account_id', $data['account_id'])
                ->findOrFail($data['contact_id']);

        $contact->throwInactive();

        $array = $this->populateData($data);

        return Document::create($array);
    }

    /**
     * Create an array with the necessary fields to create the document object.
     *
     * @return array
     */
    private function populateData($data)
    {
        $document = $data['document'];

        $data = [
            'account_id' => $data['account_id'],
            'contact_id' => $data['contact_id'],
            'original_filename' => $document->getClientOriginalName(),
            'filesize' => $document->getSize(),
            'type' => $document->guessClientExtension(),
            'mime_type' => (new \Mimey\MimeTypes)->getMimeType($document->guessClientExtension()),
        ];

        $filename = $document->store('documents', [
            'disk' => config('filesystems.default'),
            'visibility' => config('filesystems.default_visibility'),
        ]);

        return array_merge($data, [
            'new_filename' => $filename,
        ]);
    }
}
