<?php

use App\Models\Account\Account;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use App\Models\Relationship\RelationshipTypeGroup;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class AddForeignKeysToRelationshipTypeGroups extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // we need to parse the type groups table to make sure that we don't have
        // "ghost" type groups that are not associated with any account
        RelationshipTypeGroup::chunk(200, function ($relationshipTypeGroups) {
            foreach ($relationshipTypeGroups as $relationshipTypeGroup) {
                try {
                    Account::findOrFail($relationshipTypeGroup->account_id);
                } catch (ModelNotFoundException $e) {
                    $relationshipTypeGroup->delete();
                    continue;
                }
            }
        });

        Schema::table('relationship_type_groups', function (Blueprint $table) {
            $table->unsignedInteger('account_id')->change();
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
        });
    }
}
