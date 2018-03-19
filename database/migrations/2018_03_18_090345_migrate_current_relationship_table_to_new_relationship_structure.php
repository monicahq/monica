 <?php

use App\Account;
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
                $relationship_type_id = $account->getRelationshipTypeByType('partner');
                $relationships = DB::table('relationships')->where('account_id', '=', $account->id)->get();

                foreach ($relationships as $relationship) {
                    // is this a bilateral relationship?
                    $bilateralRow = DB::table('relationships')
                                            ->where('account_id', '=', $account->id)
                                            ->where('contact_id', '=', $relationship->with_contact_id)
                                            ->where('with_contact_id', '=', $relationship->contact_id)
                                            ->get();

                    // if we find such a relationship, we need to delete it - otherwise we will
                    // create a duplicate entry in the relationship table
                    if (! is_null($bilateralRow)) {
                        DB::table('relationships')->where('id', '=', $bilateralRow->id)->delete();
                    }

                    DB::table('temp_relationships_table')->insertGetId([
                        'account_id' => $account->id,
                        'contact_id_main' => $relationship->contact_id,
                        'contact_id_secondary' => $relationship->with_contact_id,
                        'relationship_type_id' => $relationship_type_id,
                        'relationship_type_name' => 'partner',
                    ]);

                    DB::table('temp_relationships_table')->insertGetId([
                        'account_id' => $account->id,
                        'contact_id_main' => $relationship->with_contact_id,
                        'contact_id_secondary' => $relationship->contact_id,
                        'relationship_type_id' => $relationship_type_id,
                        'relationship_type_name' => 'partner',
                    ]);
                }
            }
        });

        Schema::dropIfExists('relationships');

        Schema::rename('temp_relationships_table', 'relationships');
    }
}
