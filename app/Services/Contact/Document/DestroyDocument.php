<?php

namespace App\Services\Contact\Document;

use App\Services\BaseService;
use App\Models\Contact\Document;
use Illuminate\Support\Facades\Storage;
use App\Exceptions\MissingParameterException;

class DestroyDocument extends BaseService
{
    /**
     * The structure that the method expects to receive as parameter.
     *
     * @var array
     */
    private $structure = [
        'account_id',
        'document_id',
    ];

    /**
     * Destroy a document.
     *
     * @param array $data
     * @return bool
     */
    public function execute(array $data) : bool
    {
        if (! $this->validateDataStructure($data, $this->structure)) {
            throw new MissingParameterException('Missing parameters');
        }

        $document = Document::where('account_id', $data['account_id'])
                    ->findOrFail($data['document_id']);

        // Delete the physical document
        // Throws FileNotFoundException
        Storage::delete($document->new_filename);

        // Delete the object in the DB
        $document->delete();

        return true;
    }
}
