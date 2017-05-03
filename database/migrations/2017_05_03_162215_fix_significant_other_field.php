<?php

use App\SignificantOther;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class FixSignificantOtherField extends Migration
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
            if ($significantother->is_birthdate_approximate == 'true') {
                $significantother->is_birthdate_approximate = 'approximate';
            }

            if ($significantother->is_birthdate_approximate == 'false') {
                $significantother->is_birthdate_approximate = 'exact';
            }
            $significantother->save();
        }
    }
}
