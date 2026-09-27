<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('oauth_clients', function (Blueprint $table): void {
            $table->uuid('id')->primary()->comment('primary key, the client id the OAuth client presents');
            $table->nullableMorphs('owner');
            $table->string('name')->comment('name the client registered under, shown on the authorization screen');
            $table->string('secret')->nullable()->comment('hash of the client secret, null for a public client such as an assistant');
            $table->string('provider')->nullable()->comment('user provider the client signs people in through, null for the default one');
            $table->text('redirect_uris')->comment('addresses the client may be sent back to after authorization, as JSON');
            $table->text('grant_types')->comment('OAuth grants the client may use, as JSON');
            $table->boolean('revoked')->comment('whether the client may no longer obtain tokens');
            $table->timestamps();
        });
    }
};
