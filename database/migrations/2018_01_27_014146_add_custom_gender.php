<?php

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
            $male = DB::table('genders')->insertGetId(['account_id' => $account->id, 'name' => 'male']);
            $female = DB::table('genders')->insertGetId(['account_id' => $account->id, 'name' => 'female']);
            $none = DB::table('genders')->insertGetId(['account_id' => $account->id, 'name' => 'other']);

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
    }
}
