<?php

use Illuminate\Support\Str;
use App\Models\Instance\Instance;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateInstanceTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('instances', function (Blueprint $table) {
            $table->increments('id');
            $table->string('uuid');
            $table->string('current_version');
            $table->string('latest_version')->nullable();
            $table->mediumText('latest_release_notes')->nullable();
            $table->integer('number_of_versions_since_current_version')->nullable();
            $table->timestamps();
        });

        $instance = new Instance;
        $instance->current_version = config('monica.app_version');
        $instance->latest_version = config('monica.app_version');
        $instance->uuid = Str::uuid();
        $instance->save();
    }
}
