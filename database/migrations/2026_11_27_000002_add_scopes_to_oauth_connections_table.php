<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('oauth_connections', function (Blueprint $table): void {
            $table->json('scopes')->nullable()->after('label')->comment('scopes the access token of the connection carries, written when the code is exchanged');
        });
    }
};
