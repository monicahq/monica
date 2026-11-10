<?php

declare(strict_types=1);

use App\Models\Account;
use App\Models\Group;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('group_notes', function (Blueprint $table): void {
            $table->id()->comment('primary key');
            $table->uuid()->unique()->comment('unguessable identifier used in URLs');
            $table->foreignIdFor(Account::class)->comment('account the note belongs to')->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Vault::class)->comment('vault the group lives in')->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Group::class)->comment('group the note is about')->constrained()->cascadeOnDelete();
            $table->text('body')->comment('what was written, as plain text');
            $table->foreignIdFor(User::class, 'created_by_user_id')->nullable()->comment('user who wrote it')->constrained()->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['group_id', 'created_at']);
        });
    }
};
