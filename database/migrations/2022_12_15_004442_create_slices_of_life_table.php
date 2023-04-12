<?php

use App\Models\File;
use App\Models\Journal;
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
            $table->foreignIdFor(Journal::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(File::class, 'file_cover_image_id')->nullable()->constrained('files')->nullOnDelete();
            $table->string('name');
            $table->string('description')->nullable();
            $table->timestamps();
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
        Schema::table('posts', function (Blueprint $table) {
            $table->dropForeign(['slice_of_life_id']);
            $table->dropColumn('slice_of_life_id');
        });
        Schema::dropIfExists('slice_of_lives');
    }
};
