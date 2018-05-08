<?php

namespace App;

use App\Jobs\AddChangelogEntry;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;

class Instance extends Model
{
    /**
     * Once migrations have been run to add a new default contact field type,
     * we need to mark the field as being migrated so we don't create another
     * default contact field type if another migration will change this table
     * in the future.
     */
    public function markDefaultContactFieldTypeAsMigrated()
    {
        DB::table('default_contact_field_types')
            ->update(['migrated' => 1]);
    }

    /**
     * Create a job to add a changelog entry for each account of the instance.
     *
     * @param int $changelogId
     */
    public function addUnreadChangelogEntry(int $changelogId)
    {
        $accounts = \App\Account::all();
        foreach ($accounts as $account) {
            AddChangelogEntry::dispatch($account, $changelogId);
        }
    }
}
