<?php

use App\Models\Instance\Instance;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Migrations\Migration;

class ChangelogAboutLifeEvent extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $description = '
**New feature: life events**

You can now log major life events that happen to a contact. Like if the contact has had a surgery, or where he travelled to. You have access to nearly 50 different possible life events to document what happens to the people you care about.

![image](/img/changelogs/2018-09-28-life-events.png)';

        $id = DB::table('changelogs')->insertGetId([
            'description' => $description,
            'created_at' => '2018-09-28',
        ]);

        $instance = Instance::first();
        $instance->addUnreadChangelogEntry($id);
    }
}
