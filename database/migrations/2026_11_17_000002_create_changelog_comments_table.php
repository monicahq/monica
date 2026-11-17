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
        Schema::create('changelog_comments', function (Blueprint $table): void {
            $table->id()->comment('primary key');
            $table->uuid()->unique()->comment('unguessable identifier used in URLs');
            $table->foreignIdFor(User::class)->comment('person who wrote the comment')->constrained()->cascadeOnDelete();
            $table->char('entry_id', 26)->comment('ULID of the changelog entry, a file in changelog/ rather than a row');
            $table->text('body')->comment('what was written, as plain text');
            $table->boolean('is_private')->default(false)->comment('true when only administrators may read it');
            $table->boolean('is_anonymous')->default(false)->comment('true when other readers see Anonymous instead of the name');
            $table->string('status')->comment('new, reviewed or hidden, a ChangelogCommentStatusEnum value');
            $table->timestamp('reviewed_at')->nullable()->comment('when an administrator last reviewed it');
            $table->timestamps();

            $table->index(['entry_id', 'status']);
        });
    }
};
