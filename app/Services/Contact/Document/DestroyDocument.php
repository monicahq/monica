<?php

namespace App\Services\Contact\Document;

use App\Services\BaseService;
use App\Models\Contact\Document;
use Illuminate\Support\Facades\Storage;

class DestroyDocument extends BaseService
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
            'document_id' => 'required|integer',
        ];
    }

    /**
     * Destroy a document.
     *
     * @param array $data
     * @return bool
     */
    public function execute(array $data) : bool
    {
        $this->validate($data);

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
