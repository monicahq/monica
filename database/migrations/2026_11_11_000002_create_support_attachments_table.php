<?php

declare(strict_types=1);

use App\Models\Account;
use App\Models\SupportMessage;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_attachments', function (Blueprint $table): void {
            $table->id()->comment('primary key');
            $table->uuid()->unique()->comment('unguessable identifier used in URLs');
            $table->foreignIdFor(Account::class)->comment('account of the user who sent it')->constrained()->cascadeOnDelete();
            $table->foreignIdFor(SupportMessage::class)->comment('message the file was sent with')->constrained()->cascadeOnDelete();
            $table->string('disk')->comment('filesystem disk the file is stored on');
            $table->string('path')->comment('where the file is on the disk, built by the application');
            $table->string('original_name')->comment('name of the file on the computer of the user');
            $table->string('mime_type')->comment('type of the file');
            $table->unsignedInteger('size_in_bytes')->comment('size of the file, which does not count against the storage limit');
            $table->timestamps();
        });
    }
};
