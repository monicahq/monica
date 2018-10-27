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

You can now archive a contact to no longer see him/her on the Dashboard, or the Contacts list. Archived contacts can still be found by search.';

        $id = DB::table('changelogs')->insertGetId([
            'description' => $description,
            'created_at' => '2018-10-19',
        ]);

        $instance = Instance::first();
        $instance->addUnreadChangelogEntry($id);
    }
}
