<?php

use App\Models\Account\Account;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use App\Models\Relationship\RelationshipType;
use App\Models\Relationship\RelationshipTypeGroup;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class AddForeignKeysToRelationshipTypes extends Migration
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
        RelationshipType::chunk(200, function ($relationshipTypes) {
            foreach ($relationshipTypes as $relationshipType) {
                try {
                    Account::findOrFail($relationshipType->account_id);
                    RelationshipTypeGroup::findOrFail($relationshipType->relationship_type_group_id);
                } catch (ModelNotFoundException $e) {
                    $relationshipType->delete();
                    continue;
                }
            }
        });

        Schema::table('relationship_types', function (Blueprint $table) {
            $table->unsignedInteger('account_id')->change();
            $table->unsignedInteger('relationship_type_group_id')->change();
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            $table->foreign('relationship_type_group_id')->references('id')->on('relationship_type_groups')->onDelete('cascade');
        });
    }
}
