<?php

use App\Models\Instance\Instance;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Migrations\Migration;

class ChangelogAboutDocuments extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $description = '
**New feature: documents upload**

You can now upload and attach documents to a contact. The only limitation is the size of those documents - apart from this limitation, you can upload any type of documents.

![image](/img/changelogs/2018-10-27-documents.png)';

        $id = DB::table('changelogs')->insertGetId([
            'description' => $description,
            'created_at' => '2018-10-27',
        ]);

        $instance = Instance::first();
        $instance->addUnreadChangelogEntry($id);
    }
}
