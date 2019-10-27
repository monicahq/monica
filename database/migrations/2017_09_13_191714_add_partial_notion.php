<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddPartialNotion extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->boolean('is_partial')->after('gender')->default(0);
        });

        $contacts = DB::table('contacts')->get();
        foreach ($contacts as $contact) {
            if ($contact->is_kid == 1 or $contact->is_significant_other == 1) {
                DB::table('contacts')
                    ->where('id', $contact->id)
                    ->update(['is_partial' => 1]);
            }
        }

        Schema::table('contacts', function (Blueprint $table) {
            $table->dropColumn(
                'is_significant_other',
                'is_kid'
            );
        });
    }
}
