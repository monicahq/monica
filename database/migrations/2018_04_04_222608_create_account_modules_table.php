<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAccountModulesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('modules', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('account_id');
            $table->string('key');
            $table->string('translation_key');
            $table->boolean('active')->default(1);
            $table->boolean('delible')->default(0);
            $table->timestamps();
        });
    }
}
