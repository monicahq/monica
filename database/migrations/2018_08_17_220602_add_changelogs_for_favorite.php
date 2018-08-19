<?php

use App\Models\Instance\Instance;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Migrations\Migration;

class AddChangelogsForFavorite extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $description = '
**New activity report page**

When viewing a contact, you now have access to a new activity report page that will display useful statistics regarding all the activities you\'ve done with a specific contact.

![image](/img/changelogs/2018-08-17-activity-report.png)';

        $id = DB::table('changelogs')->insertGetId([
            'description' => $description,
            'created_at' => '2018-08-17',
        ]);

        $instance = Instance::first();
        $instance->addUnreadChangelogEntry($id);

        $description = '
**Set as favorite**

You can now set a contact as favorite. Favorites will always appear at the top of the contact list, no matter the filter you are using.

![image](/img/changelogs/2018-08-17-favorite.png)';

        $id = DB::table('changelogs')->insertGetId([
            'description' => $description,
            'created_at' => '2018-08-17',
        ]);

        $instance = Instance::first();
        $instance->addUnreadChangelogEntry($id);
    }
}
