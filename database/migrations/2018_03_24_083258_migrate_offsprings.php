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
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Migrations\Migration;

class MigrateOffsprings extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Account::chunk(200, function ($accounts) {
            foreach ($accounts as $account) {
                $relationshipChildTypeId = $account->getRelationshipTypeByType('child')->id;
                $relationshipParentTypeId = $account->getRelationshipTypeByType('parent')->id;
                $offsprings = DB::table('offsprings')->where('account_id', $account->id)->get();

                foreach ($offsprings as $offspring) {
                    DB::table('relationships')->insert([
                        [
                            'account_id' => $account->id,
                            'contact_is' => $offspring->is_the_child_of,
                            'relationship_type_name' => 'child',
                            'of_contact' => $offspring->contact_id,
                            'relationship_type_id' => $relationshipChildTypeId,
                        ],
                        [
                            'account_id' => $account->id,
                            'contact_is' => $offspring->contact_id,
                            'relationship_type_name' => 'parent',
                            'of_contact' => $offspring->is_the_child_of,
                            'relationship_type_id' => $relationshipParentTypeId,
                        ],
                    ]);
                }
            }
        });

        Schema::dropIfExists('offsprings');
        Schema::dropIfExists('progenitors');
    }
}
