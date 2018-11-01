<?php

namespace App\Services\Contact\Document;

use App\Services\BaseService;
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
     * @param array $data
     * @return Document
     */
    public function execute(array $data) : Document
    {
        $this->validate($data);

        Contact::where('account_id', $data['account_id'])
                ->findOrFail($data['contact_id']);

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
            'filesize' => $document->getClientSize(),
            'type' => $document->guessClientExtension(),
            'mime_type' => (new \Mimey\MimeTypes)->getMimeType($document->guessClientExtension()),
        ];

        $filename = $document->storePublicly('documents', config('filesystems.default'));

        return array_merge($data, [
            'new_filename' => $filename,
        ]);
    }
}
