<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeGiftColumnStructure extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('gifts', function (Blueprint $table) {
            $table->integer('about_object_id')->change();
        });

        Schema::table('gifts', function ($table) {
            $table->dropColumn([
                'about_object_type',
            ]);
        });

        Schema::table('gifts', function ($table) {
            $table->boolean('is_is_an_idea')->after('is_an_idea');
            $table->boolean('is_has_been_offered')->after('has_been_offered');
        });

        $gifts = DB::table('gifts')->get();

        foreach ($gifts as $gift) {
            if ($gift->is_an_idea == 'true') {
                DB::table('gifts')
                    ->where('id', $gift->id)
                    ->update(['is_is_an_idea' => 1]);
            } else {
                DB::table('gifts')
                    ->where('id', $gift->id)
                    ->update(['is_is_an_idea' => 0]);
            }

            if ($gift->has_been_offered == 'true') {
                DB::table('gifts')
                    ->where('id', $gift->id)
                    ->update(['is_has_been_offered' => 1]);
            } else {
                DB::table('gifts')
                    ->where('id', $gift->id)
                    ->update(['is_has_been_offered' => 0]);
            }
        }

        Schema::table('gifts', function (Blueprint $table) {
            $table->dropColumn('is_an_idea');
            $table->dropColumn('has_been_offered');
        });

        Schema::table('gifts', function ($table) {
            $table->renameColumn('is_is_an_idea', 'is_an_idea');
            $table->renameColumn('is_has_been_offered', 'has_been_offered');
        });
    }
}
