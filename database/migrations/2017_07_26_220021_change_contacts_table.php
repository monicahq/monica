<?php

use App\Contact;
use App\Relationship;
use App\SignificantOther;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeContactsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('contacts', function ($table) {
            $table->boolean('is_significant_other')->after('gender')->default(0);
            $table->boolean('is_kid')->after('is_significant_other')->default(0);
            $table->dropColumn(
                'has_kids', 'number_of_kids', 'nature_of_relationship'
            );
        });

        Schema::table('significant_others', function ($table) {
            $table->integer('temp_contact_id');
        });

        foreach (SignificantOther::all() as $significantOther) {
            $contact = new Contact;
            $contact->account_id = $significantOther->account_id;
            $contact->first_name = $significantOther->first_name;
            $contact->gender = $significantOther->gender;
            $contact->is_birthdate_approximate = $significantOther->is_birthdate_approximate;
            $contact->birthdate = $significantOther->birthdate;
            $contact->is_significant_other = 1;
            $contact->created_at = $significantOther->created_at;
            $contact->updated_at = $significantOther->updated_at;
            $contact->save();

            $significantOther->temp_contact_id = $contact->id;
            $significantOther->save();
        }

        Schema::create('relationships', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('account_id');
            $table->integer('contact_id');
            $table->integer('with_contact_id');
            $table->dateTime('anniversary')->nullable();
            $table->boolean('is_active')->default(1);
            $table->string('breakup_reason', 1000)->nullable();
            $table->timestamps();
        });

        foreach (SignificantOther::all() as $significantOther) {
            $relationship = new Relationship;
            $relationship->account_id = $significantOther->account_id;
            $relationship->contact_id = $significantOther->contact_id;
            $relationship->with_contact_id = $significantOther->temp_contact_id;
            $relationship->save();
        }
    }
}
