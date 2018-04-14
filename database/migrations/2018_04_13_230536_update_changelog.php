<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
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

There is a new header now in Monica. It shows a bell that slowly pulsate, with a red dot, if there is a new feature or an important change in the application. You will not have to find out by yourself what has changed in the product.';

        DB::table('changelogs')->insert([
            'description' => $description,
            'created_at' => \Carbon\Carbon::now(),
        ]);
    }
}
