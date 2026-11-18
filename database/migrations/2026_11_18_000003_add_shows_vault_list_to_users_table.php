<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->boolean('shows_vault_list')->default(true)->after('uses_readable_addresses')->comment('whether the list of vaults is shown, or the default vault opened in its place');
        });
    }
};
