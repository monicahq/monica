<?php

declare(strict_types=1);

use App\Models\Account;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table): void {
            $table->id()->comment('primary key');
            $table->foreignIdFor(Account::class)->comment('account that paid')->constrained()->cascadeOnDelete();
            $table->string('stripe_id')->unique()->comment('invoice identifier at Stripe, which is what makes a repeated confirmation recognisable');
            $table->string('stripe_subscription_id')->nullable()->index()->comment('subscription the invoice was raised for, null for the one time purchase, which is how the kind is derived');
            $table->string('kind', 32)->nullable()->comment('offer paid for, an OfferKindEnum value, stored so the history survives a retagged price, null when the catalogue no longer knew the price it charged');
            $table->string('status', 32)->comment('invoice state as Stripe names it');
            $table->unsignedBigInteger('total')->comment('what was paid, in the smallest unit of the currency');
            $table->unsignedBigInteger('tax')->comment('the tax share of that total, worked out by Stripe from the buyer location');
            $table->string('currency', 3)->comment('currency of both amounts, three letters as Stripe writes them');
            $table->string('invoice_number')->nullable()->comment('number Stripe gave the invoice');
            $table->string('hosted_invoice_url')->nullable()->comment('page at Stripe where the owner reads the invoice');
            $table->string('invoice_pdf_url')->nullable()->comment('the same invoice as a file');
            $table->datetime('billed_at')->comment('when Stripe finalised the invoice, the sort key of the history');
            $table->timestamps();

            $table->index(['account_id', 'billed_at']);
        });
    }
};
