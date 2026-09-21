<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('files', function (Blueprint $table): void {
            $table->id()->comment('primary key');
            $table->uuid()->unique()->comment('unguessable identifier used in every path and URL, never the id');
            $table->unsignedBigInteger('account_id')->comment('account the file belongs to');
            $table->unsignedBigInteger('vault_id')->nullable()->comment('vault the file belongs to, null for account level files such as the user photo');
            $table->string('disk', 32)->comment('Laravel disk the file was written to, so a moved instance still resolves old files');
            $table->string('path', 512)->comment('path on that disk, always under the account and vault prefix');
            $table->string('original_name')->comment('name the file had on the machine it came from');
            $table->string('mime_type', 127)->comment('mime type as detected on upload, not as claimed by the browser');
            $table->unsignedBigInteger('size_in_bytes')->comment('size, counted against the account quota');
            $table->string('checksum', 64)->comment('hash of the contents, used to spot a duplicate upload');
            $table->unsignedInteger('width')->nullable()->comment('width in pixels for an image, null otherwise');
            $table->unsignedInteger('height')->nullable()->comment('height in pixels for an image, null otherwise');
            $table->json('variants')->nullable()->comment('the derived sizes written beside it, each with its suffix and pixel size');
            $table->unsignedInteger('reference_count')->default(0)->comment('how many values or life events currently point at it, zero meaning orphan');
            $table->timestamp('orphaned_at')->nullable()->comment('when the count last reached zero, which starts the grace period');
            $table->unsignedBigInteger('uploaded_by_user_id')->nullable()->comment('user who uploaded it, null when an import or a module did');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['account_id', 'orphaned_at']);
            $table->index(['vault_id', 'checksum']);

            $table->foreign('account_id')->references('id')->on('accounts')->cascadeOnDelete();
            $table->foreign('vault_id')->references('id')->on('vaults')->cascadeOnDelete();
            $table->foreign('uploaded_by_user_id')->references('id')->on('users')->nullOnDelete();
        });
    }
};
