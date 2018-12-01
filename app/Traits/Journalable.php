<?php

/**
 *  This file is part of Monica.
 *
 *  Monica is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  Monica is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with Monica.  If not, see <https://www.gnu.org/licenses/>.
 **/



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
