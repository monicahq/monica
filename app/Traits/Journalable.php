<?php

namespace App\Traits;

use App\Models\Journal\JournalEntry;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait Journalable
{
    /**
     * Get all journal entries.
     *
     * @return MorphMany
     */
    public function journalEntries()
    {
        return $this->morphMany(JournalEntry::class, 'journalable');
    }

    /**
     * Get the journal record associated.
     *
     * @return MorphOne
     */
    public function journalEntry()
    {
        return $this->morphOne(JournalEntry::class, 'journalable');
    }

    /**
     * Delete the Journal Entry associated with the given object.
     *
     * @return bool
     */
    public function deleteJournalEntry()
    {
        if ($this->journalEntry) {
            $this->journalEntry->delete();

            return true;
        }

        return false;
    }
}
