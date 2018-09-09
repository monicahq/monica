<?php

use App\Models\Instance\Instance;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Migrations\Migration;

class UpdateChangelogForConversations extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $description = '
**New feature: conversations**

You can now log conversations that you have on social media or SMS or else. While you could already do that using notes, conversations is an easier way to record them using a nice user interface.

![image](/img/changelogs/2018-09-04-conversations.png)';

        $id = DB::table('changelogs')->insertGetId([
            'description' => $description,
            'created_at' => '2018-09-04',
        ]);

        $instance = Instance::first();
        $instance->addUnreadChangelogEntry($id);
    }
}
