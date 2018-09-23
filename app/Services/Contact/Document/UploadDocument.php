<?php

namespace App\Services\Contact\Document;

use App\Services\BaseService;
use App\Models\Contact\Contact;
use App\Models\Contact\Document;
use App\Exceptions\MissingParameterException;

class UploadDocument extends BaseService
{
    /**
    * The structure that the method expects to receive as parameter.
    *
    * @var array
    */
    private $structure = [
        'account_id',
        'contact_id',
        'document',
    ];

    /**
    * Upload a document.
    *
    * @param array $data
    * @return Document
    */
    public function execute(array $data) : Document
    {
        if (! $this->validateDataStructure($data, $this->structure)) {
            throw new MissingParameterException('Missing parameters');
        }

        Contact::where('account_id', $data['account_id'])
                ->findOrFail($data['contact_id']);

        $document = $data['document'];

        $data = [
            'account_id' => $data['account_id'],
            'contact_id' => $data['contact_id'],
            'filename' => $document->getClientOriginalName(),
            'filesize' => $document->getClientSize(),
            'type' => $document->guessClientExtension()
        ];

        $filename = $document->storePublicly('avatars', config('filesystems.default'));

        return Document::create($data);
    }
}
