<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Migrations\Migration;

class UpdateChangelogWithStayInTouch extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $description = '
**Stay in touch with your contacts**

You can now indicate if you want to stay in touch with someone at a regular interval. If you do, you will receive an email at a given number of days that you decide, reminding you to contact the person. This feature is available only if you have a paid account.

![image](/img/changelogs/2018-04-21-stayintouch.gif)';

        $id = DB::table('changelogs')->insertGetId([
            'description' => $description,
            'created_at' => '2018-04-21',
        ]);

        $instance = \App\Instance::first();
        $instance->addUnreadChangelogEntry($id);
    }
}
