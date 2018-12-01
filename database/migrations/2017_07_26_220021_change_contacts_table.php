<?php

/**
 *  This file is part of Monica.
 *
 *  Monica is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  Monica is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with Monica.  If not, see <https://www.gnu.org/licenses/>.
 **/



use App\Models\Contact\Contact;
use Illuminate\Support\Facades\DB;
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

        $significantOthers = DB::table('significant_others')->get();

        foreach ($significantOthers as $significantOther) {
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

            DB::table('significant_others')
                ->where('id', $significantOther->id)
                ->update(['temp_contact_id' => $contact->id]);
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

        $significantOthers = DB::table('significant_others')->get();

        foreach ($significantOthers as $significantOther) {
            DB::table('relationships')->insert([
                'account_id' => $significantOther->account_id,
                'contact_id' => $significantOther->contact_id,
                'with_contact_id' => $significantOther->temp_contact_id,
            ]);
        }

        Schema::drop('significant_others');
    }
}
