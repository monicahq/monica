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
use App\Models\Relationship\Relationship;
use Illuminate\Database\Migrations\Migration;

class MigrateCurrentRelationshipTableToNewRelationshipStructure extends Migration
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
                $relationshipTypeId = $account->getRelationshipTypeByType('partner')->id;
                $itemsToDelete = [];

                $relationships = Relationship::where('account_id', $account->id)->get()->keyBy('id');

                foreach ($relationships as $relationship) {
                    foreach ($relationships as $bilateralRelationship) {
                        if ($relationship->contact_id == $bilateralRelationship->with_contact_id
                            && $relationship->with_contact_id == $bilateralRelationship->contact_id) {
                            $relationships->forget($bilateralRelationship->id);
                        }
                    }

                    DB::table('temp_relationships_table')->insert([
                        [
                            'account_id' => $account->id,
                            'contact_is' => $relationship->contact_id,
                            'relationship_type_name' => 'partner',
                            'of_contact' => $relationship->with_contact_id,
                            'relationship_type_id' => $relationshipTypeId,
                        ],
                        [
                            'account_id' => $account->id,
                            'contact_is' => $relationship->with_contact_id,
                            'relationship_type_name' => 'partner',
                            'of_contact' => $relationship->contact_id,
                            'relationship_type_id' => $relationshipTypeId,
                        ],
                    ]);
                }
            }
        });

        Schema::dropIfExists('relationships');

        Schema::rename('temp_relationships_table', 'relationships');
    }
}
