 <?php

use App\Account;
use App\Relationship;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
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

                foreach($relationships as $relationship) {
                    foreach ($relationships as $bilateralRelationship) {
                        if ($relationship->contact_id == $bilateralRelationship->with_contact_id
                            && $relationship->with_contact_id == $bilateralRelationship->contact_id) {
                            $relationships->forget($bilateralRelationship->id);
                        }
                    }

                    DB::table('temp_relationships_table')->insert([
                        [
                            'account_id' => $account->id,
                            'contact_id_main' => $relationship->contact_id,
                            'contact_id_secondary' => $relationship->with_contact_id,
                            'relationship_type_id' => $relationshipTypeId,
                            'relationship_type_name' => 'partner',
                        ],
                        [
                            'account_id' => $account->id,
                            'contact_id_main' => $relationship->with_contact_id,
                            'contact_id_secondary' => $relationship->contact_id,
                            'relationship_type_id' => $relationshipTypeId,
                            'relationship_type_name' => 'partner',
                        ]
                    ]);
                }
            }
        });

        Schema::dropIfExists('relationships');

        Schema::rename('temp_relationships_table', 'relationships');
    }
}
