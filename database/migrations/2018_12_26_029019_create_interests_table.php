<?php

use DB;
use App\Models\Account\Account;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use App\Services\Auth\Population\PopulateModulesTable;

class CreateInterestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('interests', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('account_id');
            $table->unsignedInteger('contact_id');
            $table->string('name');
            $table->unique(['contact_id', 'name']);
            $table->timestamps();
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            $table->foreign('contact_id')->references('id')->on('contacts')->onDelete('cascade');
        });

        DB::table('default_contact_modules')->insert([
                'key' => 'interests',
                'translation_key' => 'people.interests_title',
            ]
        );

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

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('interests');
    }
}
