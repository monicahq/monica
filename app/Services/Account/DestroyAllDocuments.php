<?php

namespace App\Services\Account;

use App\Services\BaseService;
use App\Models\Contact\Document;
use Illuminate\Support\Facades\Storage;
use Illuminate\Contracts\Filesystem\FileNotFoundException;

class DestroyAllDocuments extends BaseService
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
        ];
    }

    /**
     * Destroy all documents in an account.
     *
     * @param array $data
     * @return bool
     */
    public function execute(array $data) : bool
    {
        $this->validate($data);

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
