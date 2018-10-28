<?php

namespace App\Services\Account;

use App\Services\BaseService;
use App\Models\Contact\Document;
use Illuminate\Support\Facades\Storage;
use App\Exceptions\MissingParameterException;
use Illuminate\Contracts\Filesystem\FileNotFoundException;

class DestroyAllDocuments extends BaseService
{
    /**
     * The structure that the method expects to receive as parameter.
     *
     * @var array
     */
    private $structure = [
        'account_id',
    ];

    /**
     * Destroy all documents in an account.
     *
     * @param array $data
     * @return bool
     */
    public function execute(array $data) : bool
    {
        if (! $this->validateDataStructure($data, $this->structure)) {
            throw new MissingParameterException('Missing parameters');
        }

        $documents = Document::where('account_id', $data['account_id'])
                                ->get();

        foreach ($documents as $document) {
            try {
                Storage::delete($document->new_filename);
            } catch (FileNotFoundException $e) {
                continue;
            }

            $document->delete();
        }

        return true;
    }
}
