<?php

use App\Contact;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class MoveKidsToContacts extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('kids', function ($table) {
            $table->integer('temp_contact_id');
        });

        $kids = DB::table('kids')->get();

        foreach ($kids as $kid) {
            $contact = new Contact;
            $contact->account_id = $kid->account_id;
            $contact->first_name = $kid->first_name;
            $contact->gender = $kid->gender;
            $contact->is_birthdate_approximate = $kid->is_birthdate_approximate;
            $contact->birthdate = $kid->birthdate;
            $contact->is_kid = 1;
            $contact->created_at = $kid->created_at;
            $contact->updated_at = $kid->updated_at;
            $contact->save();

            DB::table('kids')
                ->where('id', $kid->id)
                ->update(['temp_contact_id' => $contact->id]);
        }

        Schema::create('offsprings', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('account_id');
            $table->integer('contact_id');
            $table->integer('is_the_child_of');
            $table->timestamps();
        });

        Schema::create('progenitors', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('account_id');
            $table->integer('contact_id');
            $table->integer('is_the_parent_of');
            $table->timestamps();
        });

        $kids = DB::table('kids')->get();

        foreach ($kids as $kid) {
            DB::table('offsprings')->insert([
                'account_id' => $kid->account_id,
                'contact_id' => $kid->child_of_contact_id,
                'is_the_child_of' => $kid->temp_contact_id,
            ]);
        }

        Schema::drop('kids');
    }
}
