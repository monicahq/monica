<?php

namespace App\Models\Instance;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

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
}
