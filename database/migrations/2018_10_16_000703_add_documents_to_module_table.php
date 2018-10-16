<?php

use App\Models\Account\Account;
use Illuminate\Database\Migrations\Migration;

class AddDocumentsToModuleTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('default_contact_modules')->insert(['key' => 'documents', 'translation_key' => 'people.document_list_title']);

        Account::chunk(200, function ($accounts) {
            foreach ($accounts as $account) {
                $account->populateModulesTable();
            }
        });

        DB::table('default_contact_modules')->update(['migrated' => 1]);
    }
}
