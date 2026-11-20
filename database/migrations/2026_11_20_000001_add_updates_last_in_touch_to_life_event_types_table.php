<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('life_event_types', function (Blueprint $table): void {
            $table->boolean('updates_last_in_touch')->default(true)->after('is_active')->comment('whether its life events set the last in touch date of the people they are recorded with');
        });
    }
};
