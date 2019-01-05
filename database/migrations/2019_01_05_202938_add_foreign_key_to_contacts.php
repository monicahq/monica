<?php

use App\Models\Contact\Contact;
use App\Models\Instance\SpecialDate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class AddForeignKeyToContacts extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // we need to parse the special date table to make sure that we don't have
        // "ghost" special date that are not associated with any contact (as it's
        // the case in production)
        $contacts = Contact::select('birthday_special_date_id')
            ->whereNotNull('birthday_special_date_id')
            ->chunk(50, function ($contacts) {
                foreach ($contacts as $contact) {
                    try {
                        SpecialDate::findOrFail($contact->birthday_special_date_id);
                    } catch (ModelNotFoundException $e) {
                        $contact->birthday_special_date_id = null;
                        $contact->save();
                        continue;
                    }
                }
            });

        $contacts = Contact::select('first_met_special_date_id')
            ->whereNotNull('first_met_special_date_id')
            ->chunk(50, function ($contacts) {
                foreach ($contacts as $contact) {
                    try {
                        SpecialDate::findOrFail($contact->first_met_special_date_id);
                    } catch (ModelNotFoundException $e) {
                        $contact->first_met_special_date_id = null;
                        $contact->save();
                        continue;
                    }
                }
            });

        $contacts = Contact::select('deceased_special_date_id')
            ->whereNotNull('deceased_special_date_id')
            ->chunk(50, function ($contacts) {
                foreach ($contacts as $contact) {
                    try {
                        SpecialDate::findOrFail($contact->deceased_special_date_id);
                    } catch (ModelNotFoundException $e) {
                        $contact->deceased_special_date_id = null;
                        $contact->save();
                        continue;
                    }
                }
            });

        Schema::table('contacts', function (Blueprint $table) {
            $table->unsignedInteger('birthday_special_date_id')->change();
            $table->unsignedInteger('first_met_special_date_id')->change();
            $table->unsignedInteger('deceased_special_date_id')->change();
            $table->foreign('birthday_special_date_id')->references('id')->on('special_dates')->onDelete('set null');
            $table->foreign('first_met_special_date_id')->references('id')->on('special_dates')->onDelete('set null');
            $table->foreign('deceased_special_date_id')->references('id')->on('special_dates')->onDelete('set null');
        });
    }
}
