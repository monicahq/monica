<?php

declare(strict_types=1);

use App\Models\Account;
use App\Models\Contact;
use App\Models\Possession;
use App\Models\PossessionCondition;
use App\Models\PossessionProvenanceEvent;
use App\Models\Vault;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('possession_loans', function (Blueprint $table): void {
            $table->id()->comment('primary key');
            $table->uuid('uuid')->unique()->comment('identifier used in addresses and by the API');
            $table->foreignIdFor(Account::class)->comment('account of the possession')->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Vault::class)->comment('vault of the possession')->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Possession::class)->comment('possession the loan belongs to')->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Contact::class)->nullable()->comment('contact of the same vault who has it or lent it; null once the contact is deleted for good')->constrained()->nullOnDelete();
            $table->string('direction')->comment('lent or borrowed, a PossessionLoanDirectionEnum value');
            $table->string('status')->comment('planned, active or returned, a PossessionLoanStatusEnum value');
            $table->date('started_on')->comment('day the loan started');
            $table->date('due_on')->nullable()->comment('day it is due back, null when no day was agreed');
            $table->date('returned_on')->nullable()->comment('day it came back, set only when returned');
            $table->foreignIdFor(PossessionCondition::class, 'condition_out_id')->nullable()->comment('condition of the account it was in when it left')->constrained()->nullOnDelete();
            $table->foreignIdFor(PossessionCondition::class, 'condition_back_id')->nullable()->comment('condition of the account it was in when it came back')->constrained()->nullOnDelete();
            $table->decimal('deposit_amount', 12, 2)->nullable()->comment('deposit left against the loan, 0 to 9999999999.99');
            $table->string('deposit_currency', 3)->nullable()->comment('currency of the deposit, a CurrencyEnum value');
            $table->text('purpose')->nullable()->comment('why it was lent or borrowed');
            $table->boolean('is_in_provenance')->default(false)->comment('whether the loan writes change of custody events into the provenance');
            $table->foreignIdFor(PossessionProvenanceEvent::class, 'departure_provenance_event_id')->nullable()->comment('provenance event recording the start of the loan')->constrained()->nullOnDelete();
            $table->foreignIdFor(PossessionProvenanceEvent::class, 'return_provenance_event_id')->nullable()->comment('provenance event recording the return')->constrained()->nullOnDelete();
            $table->timestamps();

            $table->index(['possession_id', 'started_on']);
        });
    }
};
