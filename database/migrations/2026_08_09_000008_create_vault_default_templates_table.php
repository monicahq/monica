<?php

declare(strict_types=1);

use App\Models\Account;
use App\Models\EntityType;
use App\Models\StructureVersion;
use App\Models\Vault;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vault_default_templates', function (Blueprint $table): void {
            $table->id()->comment('primary key');
            $table->foreignIdFor(Account::class)->comment('account the default belongs to')->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Vault::class)->comment('vault the default applies in')->constrained()->cascadeOnDelete();
            $table->string('applies_to', 32)->comment('what kind of record the default is for');
            $table->foreignIdFor(EntityType::class)->nullable()->comment('type the default is for when applies_to is entity, null otherwise')->constrained()->cascadeOnDelete();
            $table->foreignIdFor(StructureVersion::class)->comment('exact version a new record starts on')->constrained();
            $table->timestamps();

            $table->unique(['vault_id', 'applies_to', 'entity_type_id'], 'vault_default_templates_unique');
        });
    }
};
