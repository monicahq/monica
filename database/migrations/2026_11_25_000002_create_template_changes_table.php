<?php

declare(strict_types=1);

use App\Models\Account;
use App\Models\EntityType;
use App\Models\StructureVersion;
use App\Models\Template;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('template_changes', function (Blueprint $table): void {
            $table->id()->comment('primary key');
            $table->uuid()->unique()->comment('unguessable identifier used in URLs, by the API, and as the source detail of its log entry');
            $table->foreignIdFor(Account::class)->comment('account of the change')->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Vault::class)->comment('vault whose records it moves')->constrained()->cascadeOnDelete();
            $table->foreignIdFor(User::class)->nullable()->comment('who confirmed it, null once that user is deleted')->constrained()->nullOnDelete();
            $table->foreignIdFor(EntityType::class)->nullable()->comment('kind of record it moves, null for contacts')->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Template::class, 'from_template_id')->comment('template the records leave')->constrained()->cascadeOnDelete();
            $table->json('from_version_ids')->comment('versions of the source template the batch was narrowed to');
            $table->foreignIdFor(StructureVersion::class)->comment('version the records move onto')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('items_total')->default(0)->comment('how many records the batch took');
            $table->unsignedInteger('moved_count')->default(0)->comment('how many records moved, written when it finishes');
            $table->unsignedInteger('skipped_count')->default(0)->comment('how many records were left as they were, written when it finishes');
            $table->unsignedInteger('failed_count')->default(0)->comment('how many records could not be moved, written when it finishes');
            $table->timestamp('ended_at')->nullable()->comment('when the last record was dealt with, null while some are still waiting');
            $table->timestamps();
        });
    }
};
