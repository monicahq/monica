<?php

declare(strict_types=1);

use App\Models\Account;
use App\Models\StructureVersion;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('record_snapshots', function (Blueprint $table): void {
            $table->id()->comment('primary key');
            $table->foreignIdFor(Account::class)->comment('account the snapshot belongs to')->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Vault::class)->comment('vault the record lives in')->constrained()->cascadeOnDelete();
            $table->string('snapshotable_type')->comment('class of the record captured, a contact or an entity');
            $table->unsignedBigInteger('snapshotable_id')->comment('id of that record');
            $table->foreignIdFor(StructureVersion::class)->comment('structure the record was on at the time')->constrained();
            $table->string('reason', 32)->comment('why the snapshot was taken');
            $table->json('payload')->comment('full state of the record, its pages, section placements, fields, values and the files they pointed at');
            $table->json('file_ids')->comment('files the snapshot references, so the orphan sweep leaves them alone');
            $table->foreignIdFor(User::class, 'created_by_user_id')->nullable()->comment('user whose action triggered it, null when the system took it')->constrained()->nullOnDelete();
            $table->timestamp('restored_at')->nullable()->comment('when this snapshot was used to restore, null while it never was');
            $table->timestamp('expires_at')->nullable()->comment('when it may be pruned, null when it is kept indefinitely');
            $table->timestamps();

            $table->index(['snapshotable_type', 'snapshotable_id', 'created_at'], 'record_snapshots_record_index');
        });
    }
};
