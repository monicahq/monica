<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEmailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('emails', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('account_id');
            $table->datetime('sent');
            $table->mediumText('content');
            $table->string('subject');
            $table->string('to');
            $table->string('from');
            $table->timestamps();
        });

        Schema::create('contact_emails', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('account_id');
            $table->integer('email_id');
            $table->integer('contact_id');
            $table->timestamps();
        });
    }

}
