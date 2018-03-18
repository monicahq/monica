<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePetsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pets', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('account_id');
            $table->integer('contact_id');
            $table->integer('pet_category_id');
            $table->string('name')->nullable();
            $table->timestamps();
        });

        Schema::create('pet_categories', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->boolean('is_common');
            $table->timestamps();
        });

        DB::table('pet_categories')->insert(['name' => 'reptile', 'is_common' => false]);
        DB::table('pet_categories')->insert(['name' => 'bird', 'is_common' => false]);
        DB::table('pet_categories')->insert(['name' => 'cat', 'is_common' => true]);
        DB::table('pet_categories')->insert(['name' => 'dog', 'is_common' => true]);
        DB::table('pet_categories')->insert(['name' => 'fish', 'is_common' => true]);
        DB::table('pet_categories')->insert(['name' => 'hamster', 'is_common' => false]);
        DB::table('pet_categories')->insert(['name' => 'horse', 'is_common' => false]);
        DB::table('pet_categories')->insert(['name' => 'rabbit', 'is_common' => false]);
        DB::table('pet_categories')->insert(['name' => 'rat', 'is_common' => false]);
        DB::table('pet_categories')->insert(['name' => 'small_animal', 'is_common' => false]);
        DB::table('pet_categories')->insert(['name' => 'other', 'is_common' => false]);
    }
}
