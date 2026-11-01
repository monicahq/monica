<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/*
 * "Group" now names a group of contacts only, so the loose block of a life event type is stored under
 * the new key; a section left on the old one would find no translation and show the English key.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('sections')
            ->where('name_translation_key', 'Fields in no group')
            ->update(['name_translation_key' => 'Fields in no block']);
    }
};
