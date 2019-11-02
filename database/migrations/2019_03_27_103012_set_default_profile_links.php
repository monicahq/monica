<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Migrations\Migration;

class SetDefaultProfileLinks extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('default_contact_field_types')
            ->where('name', '=', 'Facebook')
            ->where('protocol', '=', null)
            ->update([
                'protocol' => 'https://facebook.com/',
            ]);

        DB::table('default_contact_field_types')
            ->where('name', '=', 'Twitter')
            ->where('protocol', '=', '')
            ->update([
                'protocol' => 'https://twitter.com/',
            ]);

        DB::table('default_contact_field_types')
            ->where('name', '=', 'WhatsApp')
            ->where('protocol', '=', null)
            ->update([
                'protocol' => 'https://wa.me/',
            ]);

        DB::table('default_contact_field_types')
            ->where('name', '=', 'LinkedIn')
            ->where('protocol', '=', '')
            ->update([
                'protocol' => 'https://linkedin.com/in/',
            ]);
    }
}
