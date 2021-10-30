<?php

namespace App\Services\Journal;

use App\Models\Journal\Entry;
use App\Services\BaseService;

class CreateJournalEntry extends BaseService
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
            'title' => 'nullable|string|max:255',
            'post' => 'required|string|max:4000000',
            'date' => 'required|date',
        ];
    }

    /**
     * Create a journal entry.
     *
     * @param  array  $data
     * @return Entry
     */
    public function execute(array $data): Entry
    {
        $this->validate($data);

        $entry = Entry::create([
            'account_id' => $data['account_id'],
            'title' => $this->nullOrValue($data, 'title'),
            'post' => $data['post'],
            'written_at' => $data['date'],
        ]);

        return $entry;
    }
}
