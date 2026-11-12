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
        Schema::create('possession_insurance_records', function (Blueprint $table): void {
            $table->id()->comment('primary key');
            $table->uuid('uuid')->unique()->comment('identifier used in addresses and by the API');
            $table->foreignIdFor(Account::class)->comment('account of the possession')->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Vault::class)->comment('vault of the possession')->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Possession::class)->comment('possession the record covers')->constrained()->cascadeOnDelete();
            $table->string('insurer')->comment('who insures the possession, as text');
            $table->string('status')->comment('active, pending, expired or cancelled');
            $table->string('policy_number')->nullable()->comment('number of the policy');
            $table->string('coverage')->nullable()->comment('what the policy covers');
            $table->decimal('insured_value', 12, 2)->comment('value the possession is insured for, 0 to 9999999999.99');
            $table->decimal('excess', 12, 2)->nullable()->comment('what is paid before the insurer pays, 0 to 9999999999.99');
            $table->string('currency', 3)->comment('currency of the insured value and the excess, a CurrencyEnum value');
            $table->date('starts_on')->nullable()->comment('day the policy period starts');
            $table->date('ends_on')->nullable()->comment('day the policy period ends or renews, null when it has no end');
            $table->boolean('is_listed_individually')->default(false)->comment('whether the policy names the possession rather than the general contents');
            $table->string('contact_name')->nullable()->comment('who to call at the insurer');
            $table->string('contact_email')->nullable()->comment('email of the contact at the insurer');
            $table->string('contact_phone', 50)->nullable()->comment('phone of the contact at the insurer');
            $table->text('note')->nullable()->comment('conditions, exclusions, what the insurer asked for');
            $table->timestamps();

            $table->index('possession_id');
        });
    }
};
