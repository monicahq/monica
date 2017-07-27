<?php

use App\Relationship;
use App\SignificantOther;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSoRelationshipsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('relationships', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('spouse_of_contact_id');
            $table->boolean('significantother_is_actual_contact_entry')->default(0);
            $table->integer('actual_contact_id')->nullable();
            $table->integer('significant_other_id')->nullable();
            $table->boolean('is_current')->default(1);
            $table->string('breakup_reason', 1000)->nullable();
            $table->timestamps();
        });

        Schema::table('significant_others', function (Blueprint $table) {
            $table->dropColumn(
                'status'
            );
        });

        foreach (SignificantOther::all() as $significantOther) {
            $relationship = new Relationship;
            $relationship->spouse_of_contact_id = $significantOther->contact_id;
            $relationship->significant_other_id = $significantOther->id;
            $relationship->save();
        }
    }
}
