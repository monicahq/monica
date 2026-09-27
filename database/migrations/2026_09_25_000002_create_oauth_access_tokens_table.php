<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('oauth_access_tokens', function (Blueprint $table): void {
            $table->char('id', 80)->primary()->comment('primary key, the identifier carried inside the signed token');
            $table->unsignedBigInteger('user_id')->nullable()->index()->comment('user the token acts as');
            $table->uuid('client_id')->comment('client the token was issued to');
            $table->string('name')->nullable()->comment('name of the token, null for one issued through authorization');
            $table->text('scopes')->nullable()->comment('scopes the token carries, as JSON');
            $table->boolean('revoked')->comment('whether the token has been withdrawn before it expired');
            $table->timestamps();
            $table->dateTime('expires_at')->nullable()->comment('when the token stops working');

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('client_id')->references('id')->on('oauth_clients')->cascadeOnDelete();
        });
    }
};
