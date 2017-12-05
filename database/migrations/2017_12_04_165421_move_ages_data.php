<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class MoveAgesData extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $contacts = DB::table('contacts')->select('account_id', 'id', 'is_birthdate_approximate', 'birthdate', 'birthday_reminder_id', 'first_met', 'deceased_date')->get();

        foreach ($contacts as $contact) {
            if ($contact->deceased_date) {
                $specialDateId = DB::table('special_dates')->insertGetId([
                    'account_id' => $contact->account_id,
                    'contact_id' => $contact->id,
                    'is_approximate' => false,
                    'date' => $contact->deceased_date,
                    'reminder_id' => null,
                    'created_at' => \Carbon\Carbon::now(),
                ]);

                DB::table('contacts')
                    ->where('id', $contact->id)
                    ->update(['deceased_special_date_id' => $specialDateId]);
            }

            if ($contact->birthdate) {
                // we don't know the date
                if ($contact->is_birthdate_approximate == 'unknown') {
                   // do nothing
                }

                // Approximate birthdate
                if ($contact->is_birthdate_approximate == 'approximate') {
                   $specialDateId = DB::table('special_dates')->insertGetId([
                        'account_id' => $contact->account_id,
                        'contact_id' => $contact->id,
                        'is_approximate' => true,
                        'date' => $contact->birthdate,
                        'reminder_id' => $contact->birthday_reminder_id,
                        'created_at' => \Carbon\Carbon::now(),
                    ]);

                    DB::table('contacts')
                        ->where('birthday_special_date_id', $specialDateId)
                        ->update(['id' => $contact->id]);

                    DB::table('reminders')
                        ->where('id', $contact->birthday_reminder_id)
                        ->update(['special_date_id' => $specialDateId]);
                }

                // Exact birthdate
                if ($contact->is_birthdate_approximate == 'exact') {
                   $specialDateId = DB::table('special_dates')->insertGetId([
                        'account_id' => $contact->account_id,
                        'contact_id' => $contact->id,
                        'is_approximate' => false,
                        'date' => $contact->birthdate,
                        'reminder_id' => $contact->birthday_reminder_id,
                        'created_at' => \Carbon\Carbon::now(),
                    ]);

                    DB::table('contacts')
                        ->where('id', $contact->id)
                        ->update(['birthday_special_date_id' => $specialDateId]);

                    DB::table('reminders')
                        ->where('id', $contact->birthday_reminder_id)
                        ->update(['special_date_id' => $specialDateId]);
                }
            }

            if ($contact->first_met) {
               $specialDateId = DB::table('special_dates')->insertGetId([
                    'account_id' => $contact->account_id,
                    'contact_id' => $contact->id,
                    'is_approximate' => false,
                    'date' => $contact->first_met,
                    'reminder_id' => null,
                    'created_at' => \Carbon\Carbon::now(),
                ]);

                DB::table('contacts')
                    ->where('id', $contact->id)
                    ->update(['first_met_special_date_id' => $specialDateId]);
            }
        }

        Schema::table('contacts', function ($table) {
            $table->dropColumn([
                'deceased_date',
                'first_met',
                'birthdate',
                'is_birthdate_approximate',
                'birthday_reminder_id',
            ]);
        });

        Schema::table('reminders', function ($table) {
            $table->dropColumn([
                'is_birthday',
            ]);
        });
    }
}
