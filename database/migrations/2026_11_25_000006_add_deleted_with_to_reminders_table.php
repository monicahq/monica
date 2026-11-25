<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reminders', function (Blueprint $table): void {
            $table->string('deleted_with_type')->nullable()->after('deleted_at')->comment('class of the record this one was deleted with, so restoring that record brings this one back');
            $table->unsignedBigInteger('deleted_with_id')->nullable()->after('deleted_with_type')->comment('record this one was deleted with; a morph, so no foreign key');

            $table->index(['deleted_with_type', 'deleted_with_id']);
        });
    }
};
