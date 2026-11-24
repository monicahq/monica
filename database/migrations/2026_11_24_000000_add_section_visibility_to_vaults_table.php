<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vaults', function (Blueprint $table): void {
            $table->boolean('shows_people')->default(true)->after('contact_count')->comment('whether the People section is shown in the sidebar and in the search of the vault');
            $table->boolean('shows_things')->default(true)->after('shows_people')->comment('whether the Things section is shown in the sidebar of the vault');
            $table->boolean('shows_knowledge')->default(true)->after('shows_things')->comment('whether the Knowledge section is shown in the sidebar of the vault');
        });
    }
};
