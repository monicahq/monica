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

namespace App\Models\Instance;

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
}
