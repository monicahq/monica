<?php

namespace App\Services\Journal;

use App\Models\Journal\Entry;
use App\Services\BaseService;

class DestroyEntry extends BaseService
{
    /**
     * Get the validation rules that apply to the service.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'account_id' => 'required|integer|exists:accounts,id',
            'id' => 'required|integer|exists:entries,id',
        ];
    }

    /**
     * Destroy an entry.
     *
     * @param array $data
     * @return bool
     */
    public function execute(array $data): bool
    {
        $this->validate($data);

        $entry = Entry::where('account_id', $data['account_id'])
            ->where('id', $data['id'])
            ->firstOrFail();

        $entry->deleteJournalEntry();

        $entry->delete();

        return true;
    }

    /**
     * Validate all datas to execute the service.
     *
     * @param array $data
     * @return bool
     */
    public function validate(array $data): bool
    {
        parent::validate($data);

        Entry::where('account_id', $data['account_id'])
            ->findOrFail($data['id']);

        return true;
    }
}
