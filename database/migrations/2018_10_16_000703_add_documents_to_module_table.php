<?php

use App\Models\Account\Account;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Migrations\Migration;
use App\Services\Auth\Population\PopulateModulesTable;

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
                (new PopulateModulesTable)->execute([
                    'account_id' => $account->id,
                    'migrate_existing_data' => false,
                ]);
            }
        });

        DB::table('default_contact_modules')->update(['migrated' => 1]);
    }
}
