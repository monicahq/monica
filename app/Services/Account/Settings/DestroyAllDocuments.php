<?php

namespace App\Services\Account\Settings;

use App\Services\BaseService;
use App\Models\Contact\Document;

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
     * @param  array  $data
     * @return bool
     */
    public function execute(array $data): bool
    {
        $this->validate($data);

        $documents = Document::where('account_id', $data['account_id'])
                                ->get();

        foreach ($documents as $document) {
            $document->delete();
        }

        return true;
    }
}
