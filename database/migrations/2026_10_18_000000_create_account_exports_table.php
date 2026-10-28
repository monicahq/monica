<?php

declare(strict_types=1);

use App\Models\Account;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('account_exports', function (Blueprint $table): void {
            $table->id()->comment('primary key');
            $table->uuid()->unique()->comment('public identifier of the export, used by the API and in the download address');
            $table->foreignIdFor(Account::class)->comment('account the data comes from')->constrained()->cascadeOnDelete();
            $table->foreignIdFor(User::class)->comment('user who asked for it')->constrained()->cascadeOnDelete();
            $table->json('vault_ids')->comment('vaults it covers, every vault of the account when it was asked');
            $table->string('status', 32)->comment('where it stands: preparing, ready, failed or expired');
            $table->string('disk', 64)->comment('filesystem disk holding the file');
            $table->string('path', 512)->comment('where the file is on that disk, under the account prefix and never built from user input');
            $table->unsignedBigInteger('size_in_bytes')->nullable()->comment('size of the file once ready, null while it is prepared');
            $table->timestamp('ready_at')->nullable()->comment('when the file was complete, null until then');
            $table->timestamp('expires_at')->nullable()->comment('seven days after ready_at, when the file is deleted and the row kept as expired');
            $table->timestamps();

            $table->index(['account_id', 'status']);
            $table->index(['status', 'expires_at']);
        });
    }
};
