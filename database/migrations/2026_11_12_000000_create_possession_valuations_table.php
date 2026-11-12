<?php

declare(strict_types=1);

use App\Models\Account;
use App\Models\Possession;
use App\Models\Vault;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('possession_valuations', function (Blueprint $table): void {
            $table->id()->comment('primary key');
            $table->uuid('uuid')->unique()->comment('identifier used in addresses and by the API');
            $table->foreignIdFor(Account::class)->comment('account of the possession')->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Vault::class)->comment('vault of the possession')->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Possession::class)->comment('possession the valuation estimates')->constrained()->cascadeOnDelete();
            $table->string('type')->comment('own_estimate, appraisal, insurance_declaration, auction_result, comparable_listing or offer_received');
            $table->date('valued_on')->comment('day the estimate applies to');
            $table->string('confidence')->default('unknown')->comment('unknown, certain, probable or uncertain');
            $table->decimal('amount', 12, 2)->comment('what the possession is worth, 0 to 9999999999.99');
            $table->string('currency', 3)->comment('currency of the amount, a CurrencyEnum value');
            $table->string('valuer')->nullable()->comment('who estimated it, as text');
            $table->string('method')->nullable()->comment('how it was estimated');
            $table->string('reference_number')->nullable()->comment('reference of the appraisal, the listing or the offer');
            $table->string('source_url')->nullable()->comment('where the valuation can be checked');
            $table->text('note')->nullable()->comment('anything worth remembering about the valuation');
            $table->timestamps();

            $table->index(['possession_id', 'valued_on']);
        });
    }
};
