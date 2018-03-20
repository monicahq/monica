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
                $relationshipTypeId = $account->getRelationshipTypeByType('partner');
                $itemsToDelete = [];

                // we have to clean the table if it contains bilateral relationships
                Relationship::where('account_id', $account->id)->chunk(200, function ($relationships) use (&$itemsToDelete, $account) {
                    foreach ($relationships as $relationship) {
                        if (in_array($relationship->id, $itemsToDelete)) {
                            continue;
                        }

                        $bilateralRow = DB::table('relationships')
                                            ->where('account_id', '=', $account->id)
                                            ->where('contact_id', '=', $relationship->with_contact_id)
                                            ->where('with_contact_id', '=', $relationship->contact_id)
                                            ->first();

                        // if we find such a relationship, we need to delete it - otherwise we will
                        // create a duplicate entry in the relationship table
                        // we can't delete it in the same for loop, otherwise we'll probably lose some
                        // data as we change page
                        if ($bilateralRow) {
                            array_push($itemsToDelete, $bilateralRow->id);
                        }
                    }
                });

                // Now we delete the rows that we don't need
                foreach ($itemsToDelete as $id) {
                    DB::table('relationships')->where('id', '=', $id)->delete();
                }

                Relationship::where('account_id', $account->id)->chunk(200, function ($relationships) use ($itemsToDelete, $account, $relationshipTypeId) {
                    foreach ($relationships as $relationship) {
                        DB::table('temp_relationships_table')->insertGetId([
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
                        ]);
                    }
                });
            }
        });

        Schema::dropIfExists('relationships');

        Schema::rename('temp_relationships_table', 'relationships');
    }
}
