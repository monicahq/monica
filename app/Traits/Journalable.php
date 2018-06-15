<?php

namespace App\Traits;

use App\Models\Journal\JournalEntry;
use Illuminate\Database\Eloquent\ModelNotFoundException;

trait Journalable
{
    /**
     * Get all journal entries.
     */
    public function journalEntries()
    {
        return $this->morphMany(JournalEntry::class, 'journalable');
    }

    /**
     * Get the journal record associated.
     */
    public function journalEntry()
    {
        return $this->morphOne(JournalEntry::class, 'journalable');
    }

    /**
     * Delete the Journal Entry associated with the given object.
     */
    public function deleteJournalEntry()
    {
        try {
            $journalEntry = JournalEntry::where('account_id', $this->account_id)
                ->where('journalable_id', $this->id)
                ->where('journalable_type', get_class($this))
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return;
        }

        $journalEntry->delete();

        return true;
    }
}
