<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Migrations\Migration;

class UpdateChangelog extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $description = '
**Introducing a new product changes section**

There is a new header now in Monica. It shows a bell that slowly pulsate, with a red dot, if there is a new feature or an important change in the application. You will not have to find out by yourself what has changed in the product.

![image](/img/changelogs/2018-04-14-new-product-section.png)';

        $id = DB::table('changelogs')->insertGetId([
            'description' => $description,
            'created_at' => '2018-04-15',
        ]);

        $instance = \App\Instance::first();
        $instance->addUnreadChangelogEntry($id);

        $description = '
**New relationships**

You now have much more control on how you link contacts together.

Before you could only have parent/child relationships and significant other relationships. Now you can have much more types of relationships, like uncle/nephew, lover, coworker, and so on. We hope you will like what we have done.

![image](/img/changelogs/2018-04-14-relationships.png)';

        $id = DB::table('changelogs')->insertGetId([
            'description' => $description,
            'created_at' => '2018-04-20',
        ]);

        $instance->addUnreadChangelogEntry($id);
    }
}
