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
        Schema::create('changelog_votes', function (Blueprint $table): void {
            $table->id()->comment('primary key');
            $table->foreignIdFor(User::class)->comment('person who voted')->constrained()->cascadeOnDelete();
            $table->char('entry_id', 26)->comment('ULID of the changelog entry, a file in changelog/ rather than a row');
            $table->string('direction')->comment('up or down, a ChangelogVoteDirectionEnum value');
            $table->timestamps();

            $table->unique(['user_id', 'entry_id']);
            $table->index('entry_id');
        });
    }
};
