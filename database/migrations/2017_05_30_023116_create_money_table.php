<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Migrations\Migration;

class CreateMoneyTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('debts', function ($table) {
            $table->increments('id');
            $table->integer('account_id');
            $table->integer('contact_id');
            $table->string('in_debt')->default('no');
            $table->string('status')->default('inprogress');
            $table->integer('amount');
            $table->longText('reason')->nullable();
            $table->timestamps();
        });
    }
}
