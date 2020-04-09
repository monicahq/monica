<?php

use App\Models\Account\Account;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddDefaultGender extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->unsignedInteger('default_gender_id')->after('default_time_reminder_is_sent')->nullable();
            $table->foreign('default_gender_id')->references('id')->on('genders')->onDelete('set null');
        });

        DB::table('genders')
            ->groupBy('account_id')
            ->select(DB::raw('min(id) as id, account_id'))
            ->orderBy('id')
            ->chunk(200, function ($genders) {
                foreach ($genders as $gender) {
                    $account = Account::find($gender->account_id);
                    if ($account) {
                        $account->default_gender_id = $gender->id;
                        $account->save();
                    }
                }
            });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->dropForeign(['default_gender_id']);
            $table->dropColumn('default_gender_id');
        });
    }
}
