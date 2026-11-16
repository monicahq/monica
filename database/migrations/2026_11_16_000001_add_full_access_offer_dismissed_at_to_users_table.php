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
        if (Schema::hasColumn('users', 'full_access_offer_dismissed_at')) {
            return;
        }

        Schema::table('users', function (Blueprint $table): void {
            $table->datetime('full_access_offer_dismissed_at')->nullable()->after('welcomed_at')->comment('when the user hid the banner offering full access, null until then');
        });
    }
};
