<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provider_connections', function (Blueprint $table): void {
            $table->id()->comment('primary key');
            $table->foreignIdFor(User::class)->comment('user the provider signs in')->constrained()->cascadeOnDelete();
            $table->string('provider')->comment('SSO provider the user signs in with');
            $table->string('sso_id')->comment('identifier the SSO provider gives this person and promises not to reuse');
            $table->timestamps();

            $table->unique(['user_id', 'provider']);
            $table->unique(['provider', 'sso_id']);
        });
    }
};
