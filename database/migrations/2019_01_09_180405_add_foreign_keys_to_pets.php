<?php

use App\Models\Contact\Note;
use App\Models\Account\Account;
use App\Models\Contact\Contact;
use App\Models\Contact\PetCategory;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class AddForeignKeysToPets extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // we need to parse the pets table to make sure that we don't have
        // "ghost" pets that are not associated with any account
        Note::chunk(200, function ($pets) {
            foreach ($pets as $pet) {
                try {
                    Account::findOrFail($pet->account_id);
                    Contact::findOrFail($pet->contact_id);
                    PetCategory::findOrFail($pet->pet_category_id);
                } catch (ModelNotFoundException $e) {
                    $pet->delete();
                    continue;
                }
            }
        });

        Schema::table('pets', function (Blueprint $table) {
            $table->unsignedInteger('account_id')->change();
            $table->unsignedInteger('contact_id')->change();
            $table->unsignedInteger('pet_category_id')->change();
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            $table->foreign('contact_id')->references('id')->on('contacts')->onDelete('cascade');
            $table->foreign('pet_category_id')->references('id')->on('pet_categories')->onDelete('cascade');
        });
    }
}
