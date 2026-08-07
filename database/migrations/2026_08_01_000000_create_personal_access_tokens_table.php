<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('personal_access_tokens', function (Blueprint $table): void {
            $table->id()->comment('primary key');
            $table->string('tokenable_type')->comment('class of the model the key acts as, which is always the user today');
            $table->unsignedBigInteger('tokenable_id')->comment('id of the model the key acts as');
            $table->string('name')->comment('name the person gave the key, so they can tell one from another');
            $table->string('token', 64)->unique()->comment('hash of the key, since the key itself is shown once and never stored');
            $table->text('abilities')->nullable()->comment('what the key is allowed to do, unused until there is an API to scope');
            $table->timestamp('last_used_at')->nullable()->comment('when the key last authenticated a request, null while it never has');
            $table->timestamp('expires_at')->nullable()->index()->comment('when the key stops working, null when it never does');
            $table->timestamps();

            $table->index(['tokenable_type', 'tokenable_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('personal_access_tokens');
    }
};
