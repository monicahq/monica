<?php

use App\Models\Account\Account;
use Illuminate\Support\Facades\Schema;
use App\Models\Relationship\Relationship;
use Illuminate\Database\Schema\Blueprint;
use App\Models\Relationship\RelationshipType;
use Illuminate\Database\Migrations\Migration;
use App\Models\Relationship\RelationshipTypeGroup;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Models\Contact\Contact;

class AddForeignKeysToRelationships extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // we need to parse the relations table to make sure that we don't have
        // "ghost" relations that are not associated with any account
        Relationship::chunk(200, function ($relationships) {
            foreach ($relationships as $relationship) {
                try {
                    Account::findOrFail($relationship->account_id);
                    Contact::findOrFail($relationship->contact_is);
                    Contact::findOrFail($relationship->of_contact);
                    RelationshipType::findOrFail($relationship->relationship_type_id);
                } catch (ModelNotFoundException $e) {
                    $relationship->delete();
                    continue;
                }
            }
        });

        Schema::table('relationships', function (Blueprint $table) {
            $table->unsignedInteger('account_id')->change();
            $table->unsignedInteger('contact_is')->change();
            $table->unsignedInteger('of_contact')->change();
            $table->unsignedInteger('relationship_type_id')->change();
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            $table->foreign('contact_is')->references('id')->on('contacts')->onDelete('cascade');
            $table->foreign('of_contact')->references('id')->on('contacts')->onDelete('cascade');
            $table->foreign('relationship_type_id')->references('id')->on('relationship_types')->onDelete('cascade');
        });
    }
}
