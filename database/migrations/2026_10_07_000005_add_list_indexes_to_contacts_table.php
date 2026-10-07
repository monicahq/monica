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
            $table->rawIndex('vault_id, deleted_at, is_archived, is_partial, is_favourite desc, sort_key', 'contacts_list_index');
            $table->index(['vault_id', 'last_consulted_at']);
        });
    }
};
