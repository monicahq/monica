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
            $table->boolean('deleted_with_vault')->default(false)->after('deleted_at')->comment('whether the contact was taken out by the deletion of its vault, so restoring the vault knows who to bring back');
        });
    }
};
