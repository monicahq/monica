<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Migrations\Migration;

class UpdateChangelogWithRemovalAutomaticBirthday extends Migration
{
    public function up()
    {
        $description = '
**Disable automatic birthday reminders**

When you edit a contact, or manage a relationship, and add a birthday, we used to add a reminder for it automatically. This has annoyed you a lot, considering the number of emails you have sent about this. We\'ve change this behaviour. You now have the option to decide if you want to be reminded for the birthday. Hope you like it!

![image](/img/changelogs/2018-05-04-screenshot-macpro.png)';

        $id = DB::table('changelogs')->insertGetId([
            'description' => $description,
            'created_at' => '2018-05-04',
        ]);

        $instance = \App\Instance::first();
        $instance->addUnreadChangelogEntry($id);
    }
}
