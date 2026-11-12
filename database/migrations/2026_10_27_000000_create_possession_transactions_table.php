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
        Schema::create('possession_transactions', function (Blueprint $table): void {
            $table->id()->comment('primary key');
            $table->uuid('uuid')->unique()->comment('identifier used in addresses and by the API');
            $table->foreignIdFor(Account::class)->comment('account of the possession')->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Vault::class)->comment('vault of the possession')->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Possession::class)->comment('possession the transaction belongs to')->constrained()->cascadeOnDelete();
            $table->string('type')->comment('purchase, sale, exchange, gift_received, gift_given, inheritance, refund, fee, tax, shipping or other');
            $table->date('occurred_on')->nullable()->comment('day it happened, null when not known');
            $table->string('counterparty')->nullable()->comment('who was on the other side, as text');
            $table->decimal('amount', 12, 2)->nullable()->comment('price or amount, 0 to 9999999999.99');
            $table->decimal('tax', 12, 2)->nullable()->comment('tax paid, 0 to 9999999999.99');
            $table->decimal('fees', 12, 2)->nullable()->comment('fees paid, 0 to 9999999999.99');
            $table->decimal('shipping', 12, 2)->nullable()->comment('shipping paid, 0 to 9999999999.99');
            $table->decimal('entered_total', 12, 2)->nullable()->comment('total typed in, overrides the sum of the four figures');
            $table->string('currency', 3)->nullable()->comment('currency of every figure, a CurrencyEnum value, set once a figure is');
            $table->string('reference')->nullable()->comment('invoice, receipt or deed number');
            $table->string('source_url', 2048)->nullable()->comment('where the transaction can be checked');
            $table->text('note')->nullable()->comment('anything worth remembering about it');
            $table->timestamps();

            $table->index(['possession_id', 'occurred_on']);
        });
    }
};
