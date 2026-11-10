<?php

declare(strict_types=1);

use App\Models\Account;
use App\Models\File;
use App\Models\Group;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('group_files', function (Blueprint $table): void {
            $table->id()->comment('primary key');
            $table->uuid()->unique()->comment('unguessable identifier used in URLs');
            $table->foreignIdFor(Account::class)->comment('account the attachment belongs to')->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Group::class)->comment('group the file is kept with')->constrained()->cascadeOnDelete();
            $table->foreignIdFor(File::class)->comment('file kept with the group, which holds one reference on it')->constrained()->cascadeOnDelete();
            $table->foreignIdFor(User::class, 'added_by_user_id')->nullable()->comment('user who added it')->constrained()->nullOnDelete();
            $table->timestamps();

            $table->unique(['group_id', 'file_id']);
        });
    }
};
