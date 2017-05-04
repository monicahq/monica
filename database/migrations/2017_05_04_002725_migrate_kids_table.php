<?php

use App\Kid;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class MigrateKidsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $kids = Kid::all();
        foreach ($kids as $kid) {
            if ($kid->is_birthdate_approximate == 'true') {
                $kid->is_birthdate_approximate = 'approximate';
            }

            if ($kid->is_birthdate_approximate == 'false') {
                $kid->is_birthdate_approximate = 'exact';
            }
            $kid->save();
        }
    }
}
