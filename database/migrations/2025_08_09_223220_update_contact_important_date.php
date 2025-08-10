<?php

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    use HasUuids;

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('contact_important_dates', function (Blueprint $table) {
            $table->string('id', 36)->change();
            $table->softDeletes();
        });

        DB::table('contact_important_dates')
            ->chunkById(100, function ($dates) {
                foreach ($dates as $date) {
                    DB::table('contact_important_dates')
                        ->where('id', $date->id)
                        ->where('contact_id', $date->contact_id)
                        ->update(['id' => $this->newUniqueId()]);
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $i = 1;
        DB::table('contact_important_dates')
            ->chunkById(100, function ($dates) use ($i) {
                foreach ($dates as $date) {
                    DB::table('contact_important_dates')
                        ->where('id', $date->id)
                        ->where('contact_id', $date->contact_id)
                        ->update(['id' => $i++]);
                }
            });

        Schema::table('contact_important_dates', function (Blueprint $table) {
            $table->id()->change();
            $table->dropSoftDeletes();
        });
    }
};
