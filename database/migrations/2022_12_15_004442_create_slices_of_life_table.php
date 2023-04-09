<?php

use App\Models\SliceOfLife;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('slice_of_lives', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('journal_id');
            $table->unsignedBigInteger('file_cover_image_id')->nullable();
            $table->string('name');
            $table->string('description')->nullable();
            $table->timestamps();
            $table->foreign('journal_id')->references('id')->on('journals')->onDelete('cascade');
            $table->foreign('file_cover_image_id')->references('id')->on('files')->onDelete('set null');
        });

        Schema::table('posts', function (Blueprint $table) {
            $table->foreignIdFor(SliceOfLife::class)->nullable()->after('journal_id')->constrained()->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('slice_of_lives');
        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn('slice_of_life_id');
        });
    }
};
