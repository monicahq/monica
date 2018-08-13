<?php

use App\Models\Instance\Instance;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Migrations\Migration;

class CreateChangelogFor250 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $description = '
**Customization of activity types**

What you do with your friends is different than what I do with my friends. Therefore, you can now add, edit or delete activity types in the Settings tab. This is a premium feature.

![image](/img/changelogs/2018-08-08-activity-types.png)';

        $id = DB::table('changelogs')->insertGetId([
            'description' => $description,
            'created_at' => '2018-08-08',
        ]);

        $instance = Instance::first();
        $instance->addUnreadChangelogEntry($id);
    }
}
