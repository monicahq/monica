<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateNotificationTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->integer('delete_after_number_of_emails_sent')->default(0)->after('reminder_id');
            $table->integer('number_of_emails_sent')->default(0)->after('delete_after_number_of_emails_sent');
        });
    }
}
