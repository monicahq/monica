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


use App\Models\Account\Account;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Migrations\Migration;

class AddExWifeHusbandRelationship extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $id = DB::table('default_relationship_type_groups')
            ->where([
                'name' => 'love',
            ])
            ->value('id');

        DB::table('default_relationship_types')->insert([
            'name' => 'ex_husband',
            'name_reverse_relationship' => 'ex_husband',
            'relationship_type_group_id' => $id,
        ]);

        // Add the default relationship type to the account relationship types
        Account::chunk(200, function ($accounts) {
            foreach ($accounts as $account) {
                /* @var Account $account */
                $account->populateRelationshipTypesTable(true);
            }
        });

        DB::table('default_relationship_types')
            ->update(['migrated' => 1]);
    }
}
