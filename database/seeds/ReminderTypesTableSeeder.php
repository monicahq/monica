<?php

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;

class ReminderTypesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('reminder_types')->truncate();

        DB::table('reminder_types')->insert([
            'description' => 'birthday',
            'translation_key' => 'reminder.type_birthday',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        DB::table('reminder_types')->insert([
            'description' => 'phone_call',
            'translation_key' => 'reminder.type_phone_call',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        DB::table('reminder_types')->insert([
            'description' => 'lunch',
            'translation_key' => 'reminder.type_lunch',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        DB::table('reminder_types')->insert([
            'description' => 'hangout',
            'translation_key' => 'reminder.type_hangout',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        DB::table('reminder_types')->insert([
            'description' => 'email',
            'translation_key' => 'reminder.type_email',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        DB::table('reminder_types')->insert([
            'description' => 'birthday_kid',
            'translation_key' => 'reminder.type_birthday_kid',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);
    }
}
