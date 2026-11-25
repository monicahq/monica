<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('entities', function (Blueprint $table): void {
            $table->index(['vault_id', 'entity_type_id', 'deleted_at', 'structure_version_id', 'is_archived'], 'entities_statistics_index');
        });
    }
};
