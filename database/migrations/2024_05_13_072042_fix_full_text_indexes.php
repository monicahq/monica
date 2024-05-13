<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $this->addFullTextIndex('contacts', ['first_name', 'last_name', 'middle_name', 'nickname', 'maiden_name']);
        $this->addFullTextIndex('groups', ['name']);
        $this->addFullTextIndex('notes', ['title', 'body']);
    }

    private function addFullTextIndex(string $name, array $columns): void
    {
        if (config('scout.full_text_index') && in_array(DB::connection()->getDriverName(), ['mysql', 'pgsql'])) {
            Schema::table($name, function (Blueprint $table) use ($columns) {
                foreach ($columns as $column) {
                    if (! Schema::hasIndex($table->getTable(), $table->getTable().'_'.$column.'_fulltext')) {
                        $table->fullText($column);
                    }
                }
            });
        }
    }
};
