<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddMultipleGendersChoices extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("ALTER TABLE contacts CHANGE COLUMN gender gender ENUM('male', 'female', 'none')");
        DB::statement("ALTER TABLE significant_others CHANGE COLUMN gender gender ENUM('male', 'female', 'none')");
        DB::statement("ALTER TABLE kids CHANGE COLUMN gender gender ENUM('male', 'female', 'none')");
    }
}
