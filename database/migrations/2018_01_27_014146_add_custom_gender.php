<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddCustomGender extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('genders', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('account_id');
            $table->string('name');
            $table->timestamps();
        });

        Schema::table('contacts', function (Blueprint $table) {
            $table->integer('gender_id')->after('gender');
        });

        $accounts = DB::table('accounts')->select('id')->get();
        foreach ($accounts as $account) {
            $user = DB::table('users')->select('locale')->where('account_id', $account->id)->first();

            if (! $user) {
                continue;
            }
            App::setLocale($user->locale);

            $male = DB::table('genders')->insertGetId(['account_id' => $account->id, 'name' => trans('app.gender_male')]);
            $female = DB::table('genders')->insertGetId(['account_id' => $account->id, 'name' => trans('app.gender_female')]);
            $none = DB::table('genders')->insertGetId(['account_id' => $account->id, 'name' => trans('app.gender_none')]);

            $contacts = DB::table('contacts')->select('id', 'gender')->where('account_id', $account->id)->get();
            foreach ($contacts as $contact) {
                if ($contact->gender == 'male') {
                    DB::table('contacts')->where('id', $contact->id)->update(['gender_id' => $male]);
                }

                if ($contact->gender == 'female') {
                    DB::table('contacts')->where('id', $contact->id)->update(['gender_id' => $female]);
                }

                if ($contact->gender == 'none') {
                    DB::table('contacts')->where('id', $contact->id)->update(['gender_id' => $none]);
                }
            }
        }

        Schema::table('contacts', function (Blueprint $table) {
            $table->dropColumn('gender');
        });
    }
}
