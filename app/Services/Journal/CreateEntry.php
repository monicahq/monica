<?php

namespace App\Services\Journal;

use App\Models\Journal\Entry;
use App\Models\Journal\JournalEntry;
use App\Services\BaseService;

class CreateEntry extends BaseService
{
    /**
     * Get the validation rules that apply to the service.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'title' => 'nullable|max:255',
            'post' => 'required|max:1000000',
            'date' => 'required|date|date_format:Y-m-d',
        ];
    }

    /**
     * Create an entry.
     *
     * @param  array  $data
     * @return Entry
     */
    public function execute(array $data): Entry
    {
        $this->validate($data);

        $entry = $this->create($data);

        // Log a journal entry
        JournalEntry::add($entry);

        return $entry;
    }

    /**
     * Create the entry.
     *
     * @param  array  $data
     * @return Entry
     */
    private function create(array $data): Entry
    {
        return Entry::create([
            'account_id' => $data['account_id'],
            'title' => $this->nullOrValue($data, 'title'),
            'post' => $data['post'],
            'date' => $data['date'],
        ]);
    }
}
