<?php

use App\Models\Account\Account;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Migrations\Migration;

class AddConversationsToModules extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // fix a mistake that we've made in the past of not indicate the default
        // contact modules have been migrated
        DB::table('default_contact_modules')->update(['migrated' => 1]);

        // now add a new module to track conversations
        DB::table('default_contact_modules')->insert(['key' => 'conversations', 'translation_key' => 'people.conversation_list_title']);

        Account::chunk(200, function ($accounts) {
            foreach ($accounts as $account) {
                $account->populateModulesTable();
            }
        });

        DB::table('default_contact_modules')->update(['migrated' => 1]);
    }
}
