<?php

declare(strict_types=1);

use App\Models\Account;
use App\Models\ShareLink;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('share_link_visits', function (Blueprint $table): void {
            $table->id()->comment('primary key');
            $table->foreignIdFor(Account::class)->comment('account the link belongs to')->constrained()->cascadeOnDelete();
            $table->foreignIdFor(ShareLink::class)->comment('link that was opened')->constrained()->cascadeOnDelete();
            $table->string('ip_address', 45)->nullable()->comment('address it was opened from, kept coarse and only for a limited time');
            $table->string('user_agent', 512)->nullable()->comment('browser it was opened in');
            $table->string('country', 2)->nullable()->comment('country the address resolves to, which is usually all the owner cares about');
            $table->boolean('password_was_required')->default(false)->comment('whether a password had to be given');
            $table->boolean('password_failed')->default(false)->comment('whether the attempt failed on the password');
            $table->timestamp('viewed_at')->comment('when it happened');
            $table->timestamps();

            $table->index(['share_link_id', 'viewed_at']);
        });
    }
};
