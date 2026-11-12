<?php

declare(strict_types=1);

use App\Models\SupportRequest;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_messages', function (Blueprint $table): void {
            $table->id()->comment('primary key');
            $table->uuid()->unique()->comment('unguessable identifier used in URLs');
            $table->foreignIdFor(SupportRequest::class)->comment('request the message belongs to')->constrained()->cascadeOnDelete();
            $table->foreignIdFor(User::class, 'author_user_id')->nullable()->comment('user who wrote it')->constrained()->nullOnDelete();
            $table->string('author_name')->comment('name of the author when it was written, kept if the user goes');
            $table->string('kind')->comment('message from the user, reply or internal note, a SupportMessageKindEnum value');
            $table->text('body')->comment('what was written, as plain text');
            $table->timestamps();

            $table->index(['support_request_id', 'created_at']);
        });
    }
};
