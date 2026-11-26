<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Laravel\Passport\Client;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mobile_sessions', function (Blueprint $table): void {
            $table->id()->comment('primary key');
            $table->foreignIdFor(User::class)->index()->comment('user signed in on the phone')->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Client::class)->comment('OAuth client of the mobile app the phone signed in through')->constrained()->cascadeOnDelete();
            $table->string('device_name')->nullable()->comment('name the phone gave itself, null when it gave none');
            $table->string('access_token_id', 80)->index()->comment('id of the current access token; no foreign key, since passport:purge deletes token rows');
            $table->char('refresh_token_hash', 64)->unique()->comment('SHA-256 of the current refresh token, never the token itself');
            $table->char('previous_refresh_token_hash', 64)->nullable()->index()->comment('SHA-256 of the refresh token it replaced, to notice it being presented again');
            $table->char('authorization_code_hash', 64)->unique()->comment('SHA-256 of the authorization code that started the session, to notice it being presented again');
            $table->dateTime('last_used_at')->comment('when the phone signed in or last renewed its access');
            $table->dateTime('ended_at')->nullable()->comment('when the session was ended, from the phone or from the web, null while it is active');
            $table->timestamps();
        });
    }
};
