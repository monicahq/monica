<?php

use App\Models\File;
use App\Models\Vault;
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
        Schema::create('files', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Vault::class)->constrained()->cascadeOnDelete();

            $table->nullableNumericMorphs('fileable');
            $table->uuid('ufileable_id')->nullable();
            $table->index(['fileable_type', 'ufileable_id']);

            $table->string('uuid');
            $table->string('original_url')->nullable();
            $table->string('cdn_url')->nullable();
            $table->string('mime_type');
            $table->string('name');
            $table->string('type');
            $table->integer('size');
            $table->timestamps();
        });

        Schema::table('contacts', function (Blueprint $table) {
            $table->foreignIdFor(File::class)->nullable()->after('company_id')->constrained()->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropForeign(['file_id']);
            $table->dropColumn('file_id');
        });
        Schema::dropIfExists('files');
    }
};
