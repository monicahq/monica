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
        Schema::create('oauth_auth_codes', function (Blueprint $table): void {
            $table->char('id', 80)->primary()->comment('primary key, the authorization code handed to the client');
            $table->foreignIdFor(User::class)->index()->comment('user who approved the client')->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Client::class)->comment('client the code was issued to')->constrained()->cascadeOnDelete();
            $table->text('scopes')->nullable()->comment('scopes the user granted, as JSON');
            $table->boolean('revoked')->comment('whether the code has been used or withdrawn');
            $table->dateTime('expires_at')->nullable()->comment('when the code stops being exchangeable for a token');
            $table->timestamps();
        });
    }
};
