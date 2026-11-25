<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contacts', function (Blueprint $table): void {
            $table->index(['vault_id', 'deleted_at', 'structure_version_id', 'is_archived', 'is_partial'], 'contacts_statistics_index');
        });
    }
};
