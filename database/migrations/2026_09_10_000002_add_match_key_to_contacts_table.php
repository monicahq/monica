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
            $table->string('match_key', 64)->default('')->after('search_key')->comment('folded family name, or the only name part there is, used to group contacts before their names are compared');

            $table->index(['vault_id', 'match_key'], 'contacts_match_key_index');
        });
    }
};
