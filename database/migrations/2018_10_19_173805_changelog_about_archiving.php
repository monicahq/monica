<?php

use App\Models\Instance\Instance;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Migrations\Migration;

class ChangelogAboutArchiving extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $description = '
**New feature: archiving contact**

You can now archive a contact to no longer see it in the Dashboard, or in the searchs. Archived contacts can still be found on the Contacts board.

![image](/img/changelogs/2018-10-19-archiving.png)';

        $id = DB::table('changelogs')->insertGetId([
            'description' => $description,
            'created_at' => '2018-10-19',
        ]);

        $instance = Instance::first();
        $instance->addUnreadChangelogEntry($id);
    }
}
