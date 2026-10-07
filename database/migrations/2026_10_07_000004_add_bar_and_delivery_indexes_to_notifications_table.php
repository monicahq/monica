<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table): void {
            $table->index(['user_id', 'channel', 'created_at']);
            $table->dropIndex(['held_until', 'sent_at']);
            $table->index(['sent_at', 'held_until']);
        });
    }
};
