<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RenameInBoundEmailsTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
      Schema::rename('in_bound_emails', 'inbound_emails');

      Schema::rename('contact_in_bound_emails', 'contact_inbound_emails');
    }

}
