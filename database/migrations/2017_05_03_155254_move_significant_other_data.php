<?php

use App\SignificantOther;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class MoveSignificantOtherData extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $significantothers = SignificantOther::all();
        foreach ($significantothers as $significantother) {
            if (! is_null($significantother->last_name) and trim($significantother->last_name) != '') {
                $significantother->first_name = $significantother->first_name.' '.$significantother->last_name;
            }

            $significantother->save();
        }

        Schema::table('significant_others', function (Blueprint $table) {
            $table->dropColumn('last_name');
        });
    }
}
