<?php

namespace App\Traits;

use App\JournalEntry;
use Illuminate\Database\Eloquent\ModelNotFoundException;

trait Journalable
{
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
