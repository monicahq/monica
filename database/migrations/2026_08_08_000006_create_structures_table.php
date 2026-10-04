<?php

declare(strict_types=1);

use App\Models\Account;
use App\Models\EntityType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('structures', function (Blueprint $table): void {
            $table->id()->comment('primary key');
            $table->uuid()->unique()->comment('unguessable identifier used by the API');
            $table->foreignIdFor(Account::class)->comment('account the structure belongs to')->constrained()->cascadeOnDelete();
            $table->string('owner_type')->comment('class of the thing that owns it, a template or a life event type');
            $table->unsignedBigInteger('owner_id')->comment('id of that owner, carrying no foreign key because the column is a morph');
            $table->string('applies_to', 32)->comment('what kind of record it describes, a contact, an entity or a life event');
            $table->foreignIdFor(EntityType::class)->nullable()->comment('type it applies to when applies_to is entity, null otherwise')->constrained()->nullOnDelete();
            $table->timestamps();

            $table->unique(['owner_type', 'owner_id'], 'structures_owner_unique');
        });
    }
};
