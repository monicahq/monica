<?php

declare(strict_types=1);

use App\Models\ChangelogComment;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('changelog_comment_replies', function (Blueprint $table): void {
            $table->id()->comment('primary key');
            $table->foreignIdFor(ChangelogComment::class)->comment('comment the reply answers')->constrained()->cascadeOnDelete();
            $table->foreignIdFor(User::class, 'author_user_id')->nullable()->comment('administrator who wrote it')->constrained()->nullOnDelete();
            $table->string('author_name')->comment('name of the administrator when it was written, kept if the user goes');
            $table->text('body')->comment('what was written, as plain text');
            $table->boolean('is_public')->comment('true when shown under the comment on What is new, false when sent by email');
            $table->timestamps();
        });
    }
};
