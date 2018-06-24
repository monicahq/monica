<?php

use App\Models\Instance\Instance;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Migrations\Migration;

class UpdateChangelogWithNicknames extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $description = '
**Support for nicknames**

Many of you have asked for it - we now support nicknames for your contacts. By default, if you set a nickname, we will display it after a contact name (like John Doe (Rambo)). You can choose how the nickname is displayed in your Settings - there are actually 7 different ways now to display a name.

![image](/img/changelogs/2018-05-21-nicknames.png)';

        $id = DB::table('changelogs')->insertGetId([
            'description' => $description,
            'created_at' => '2018-05-21',
        ]);

        $instance = Instance::first();
        $instance->addUnreadChangelogEntry($id);
    }
}
