<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The column was first added by editing create_users_table, so only databases created after that edit have it.
        if (Schema::hasColumn('users', 'two_factor_last_timestep')) {
            return;
        }

        Schema::table('users', function (Blueprint $table): void {
            $table->unsignedBigInteger('two_factor_last_timestep')->nullable()->after('two_factor_recovery_codes')->comment('time step of the last authenticator code accepted, so the same code cannot be used twice');
        });
    }
};
