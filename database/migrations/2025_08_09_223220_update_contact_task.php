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
        Schema::table('contact_tasks', function (Blueprint $table) {
            $table->string('id', 36)->change();
            $table->softDeletes();
        });

        DB::table('contact_tasks')
            ->chunkById(100, function ($tasks) {
                foreach ($tasks as $task) {
                    DB::table('contact_tasks')
                        ->where('id', $task->id)
                        ->where('contact_id', $task->contact_id)
                        ->where('author_id', $task->author_id)
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
        DB::table('contact_tasks')
            ->orderBy('id')
            ->chunkById(1000, function ($tasks) use ($i) {
                foreach ($tasks as $task) {
                    DB::table('contact_tasks')
                        ->where('id', $task->id)
                        ->where('contact_id', $task->contact_id)
                        ->where('author_id', $task->author_id)
                        ->update(['id' => $i++]);
                }
            });

        Schema::table('contact_tasks', function (Blueprint $table) {
            $table->id()->change();
            $table->dropSoftDeletes();
        });
    }
};
