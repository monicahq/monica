<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
 * Every live rule starts due, so the next nightly pass expands it once and writes the real instant;
 * a rule left at null would never be picked up again until somebody edited it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reminders', function (Blueprint $table): void {
            $table->timestamp('generate_from')->nullable()->after('timezone')->comment('instant the rolling window reaches the first deadline not yet written, which is when the nightly pass next has work for this rule; null when no deadline is left to write')->index();
        });

        DB::table('reminders')
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->update(['generate_from' => now()]);
    }
};
