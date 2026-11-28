<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::rename('mobile_sessions', 'oauth_connections');

        Schema::table('oauth_connections', function (Blueprint $table): void {
            $table->renameColumn('device_name', 'label');
        });

        Schema::table('oauth_connections', function (Blueprint $table): void {
            $table->string('label')->nullable()->comment('name the app gave itself, null when it gave none')->change();
            $table->string('access_token_id', 80)->nullable()->comment('id of the current access token, null for an earlier connection; no foreign key, since passport:purge deletes token rows')->change();
            $table->char('refresh_token_hash', 64)->nullable()->comment('SHA-256 of the current refresh token, never the token itself; null for an earlier connection')->change();
            $table->char('authorization_code_hash', 64)->nullable()->comment('SHA-256 of the authorization code that started the connection, to notice it being presented again; null for an earlier connection')->change();
            $table->dateTime('last_used_at')->comment('when the app connected or last renewed its access')->change();
            $table->dateTime('ended_at')->nullable()->comment('when the connection was ended, from the app or from the web, null while it is active')->change();
        });
    }
};
