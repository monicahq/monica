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
        Schema::create('security_keys', function (Blueprint $table): void {
            $table->id()->comment('primary key');
            $table->foreignIdFor(User::class)->index()->comment('user the key answers the two factor challenge for')->constrained()->cascadeOnDelete();
            $table->string('name')->comment('what its owner calls the key');
            $table->string('credential_id')->unique()->comment('WebAuthn credential id, base64url without padding');
            $table->json('credential')->comment('serialised WebAuthn credential record, rewritten at every use for the sign counter');
            $table->timestamp('last_used_at')->nullable()->comment('when the key last answered a challenge, null until it first does');
            $table->timestamps();
        });
    }
};
