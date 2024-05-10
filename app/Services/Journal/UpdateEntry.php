<?php

namespace App\Services\Journal;

use App\Models\Journal\Entry;
use App\Models\Journal\JournalEntry;
use App\Services\BaseService;

class UpdateEntry extends BaseService
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
            'title' => 'nullable|max:255',
            'post' => 'required|max:1000000',
            'date' => 'required|date|date_format:Y-m-d',
        ];
    }

    /**
     * Update an entry.
     */
    public function execute(array $data): Entry
    {
        $this->validate($data);

        $entry = Entry::where('account_id', $data['account_id'])
            ->where('id', $data['id'])
            ->firstOrFail();

        $this->update($data, $entry);

        // Log a journal entry but need to delete the previous one first
        $entry->deleteJournalEntry();
        JournalEntry::add($entry);

        return $entry->refresh();
    }

    /**
     * Validate the update.
     */
    public function validate(array $data): bool
    {
        parent::validate($data);

        Entry::where('account_id', $data['account_id'])
            ->findOrFail($data['id']);

        return true;
    }

    /**
     * Update the entry.
     */
    private function update(array $data, Entry $entry): void
    {
        $entry->update([
            'title' => $this->nullOrValue($data, 'title'),
            'post' => $data['post'],
            'date' => $data['date'],
        ]);

    }
}
