<?php

declare(strict_types=1);

use App\Models\Account;
use App\Models\StructureVersion;
use App\Models\TemplateChange;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('template_change_items', function (Blueprint $table): void {
            $table->id()->comment('primary key');
            $table->foreignIdFor(Account::class)->comment('account of the change')->constrained()->cascadeOnDelete();
            $table->foreignIdFor(TemplateChange::class)->comment('change it belongs to')->constrained()->cascadeOnDelete();
            $table->string('record_type', 64)->comment('morph type of the record to move');
            $table->unsignedBigInteger('record_id')->comment('record to move, carrying no foreign key since it is one half of a morph');
            $table->foreignIdFor(StructureVersion::class, 'from_version_id')->comment('version the record was on when the change was confirmed')->constrained()->cascadeOnDelete();
            $table->string('outcome', 16)->nullable()->comment('moved, skipped or failed, null until dealt with, and never written again once set');
            $table->string('reason', 32)->nullable()->comment('why it was skipped or failed: deleted, archived, changed_after_preview, confirmed_by_deleted_user or move_failed, null otherwise');
            $table->timestamps();

            $table->index(['template_change_id', 'outcome']);
            $table->index(['record_type', 'record_id']);
        });
    }
};
