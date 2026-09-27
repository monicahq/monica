<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('oauth_refresh_tokens', function (Blueprint $table): void {
            $table->char('id', 80)->primary()->comment('primary key, the refresh token handed to the client');
            $table->char('access_token_id', 80)->index()->comment('access token this refresh token renews, with no foreign key because the purge deletes an expired access token while its refresh token still works');
            $table->boolean('revoked')->comment('whether the refresh token has been used or withdrawn');
            $table->dateTime('expires_at')->nullable()->comment('when the refresh token stops working');
            $table->timestamps();
        });
    }
};
