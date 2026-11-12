<?php

declare(strict_types=1);

use App\Models\Account;
use App\Models\Possession;
use App\Models\PossessionCondition;
use App\Models\Vault;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('possession_maintenance_records', function (Blueprint $table): void {
            $table->id()->comment('primary key');
            $table->uuid('uuid')->unique()->comment('identifier used in addresses and by the API');
            $table->foreignIdFor(Account::class)->comment('account of the possession')->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Vault::class)->comment('vault of the possession')->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Possession::class)->comment('possession the job was done on')->constrained()->cascadeOnDelete();
            $table->string('type')->comment('a PossessionMaintenanceTypeEnum value');
            $table->string('title')->comment('what was done');
            $table->string('performed_by')->nullable()->comment('who did the job, as text');
            $table->date('performed_on')->comment('day the job was done');
            $table->decimal('cost', 12, 2)->nullable()->comment('what the job cost, 0 to 9999999999.99');
            $table->string('currency', 3)->nullable()->comment('currency of the cost, a CurrencyEnum value');
            $table->foreignIdFor(PossessionCondition::class, 'condition_before_id')->nullable()->comment('condition of the possession before the job')->constrained('possession_conditions')->nullOnDelete();
            $table->foreignIdFor(PossessionCondition::class, 'condition_after_id')->nullable()->comment('condition of the possession after the job')->constrained('possession_conditions')->nullOnDelete();
            $table->date('next_due_on')->nullable()->comment('day the job should be done again');
            $table->text('description')->nullable()->comment('what was found and what was done');
            $table->timestamps();

            $table->index('possession_id');
        });
    }
};
