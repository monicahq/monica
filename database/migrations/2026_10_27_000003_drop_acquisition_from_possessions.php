<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('possessions', function (Blueprint $table): void {
            $table->dropForeign(['acquired_from_contact_id']);
        });

        Schema::table('possessions', function (Blueprint $table): void {
            $table->dropColumn(['acquisition', 'acquired_from_contact_id', 'acquired_on']);
        });
    }
};
