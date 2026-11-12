<?php

declare(strict_types=1);

use App\Models\Account;
use App\Models\Contact;
use App\Models\Location;
use App\Models\Possession;
use App\Models\PossessionField;
use App\Models\PossessionType;
use App\Models\Vault;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('possessions', function (Blueprint $table): void {
            $table->id()->comment('primary key');
            $table->uuid('uuid')->unique()->comment('identifier used in addresses and by the API');
            $table->foreignIdFor(Account::class)->comment('account of the vault the possession is recorded in')->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Vault::class)->comment('vault the possession is recorded in')->constrained()->cascadeOnDelete();
            $table->foreignIdFor(PossessionType::class)->comment('type of the possession, same account, never changes')->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Location::class, 'current_location_id')->nullable()->comment('location where the possession is now, same account, null when unknown')->constrained('locations')->nullOnDelete();
            $table->string('name')->comment('what the possession is called');
            $table->text('description')->nullable()->comment('free description, up to 2,000 characters');
            $table->string('identifier')->nullable()->comment('serial number, vehicle identification number, frame number');
            $table->string('status')->default('owned')->comment('owned, ordered, lent, sold, given_away, lost, stolen or thrown_away');
            $table->unsignedInteger('quantity')->default(1)->comment('how many, 1 to 1,000,000');
            $table->boolean('is_featured')->default(false)->comment('shown among the main possessions of the vault');
            $table->unsignedInteger('position')->nullable()->comment('order among the featured possessions of the vault, from 1, null when not featured');
            $table->string('acquisition')->nullable()->comment('how it was acquired: bought, gift, inherited or made, null when not recorded');
            $table->foreignIdFor(Contact::class, 'acquired_from_contact_id')->nullable()->comment('contact it came from, same vault, for a gift or an inheritance only')->constrained('contacts')->nullOnDelete();
            $table->date('acquired_on')->nullable()->comment('day it was acquired, not after the day it was recorded');
            $table->decimal('declared_value', 12, 2)->nullable()->comment('what it would cost to replace today, 0 to 9999999999.99');
            $table->string('declared_value_currency', 3)->nullable()->comment('currency of the declared value, a CurrencyEnum value, set only with a declared value');
            $table->timestamps();

            $table->index(['vault_id', 'is_featured', 'position']);
            $table->index(['vault_id', 'name']);
        });

        Schema::create('possession_field_values', function (Blueprint $table): void {
            $table->id()->comment('primary key');
            $table->foreignIdFor(Account::class)->comment('account of the possession')->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Possession::class)->comment('possession holding the value')->constrained()->cascadeOnDelete();
            $table->foreignIdFor(PossessionField::class)->comment('field the value answers, a field of the possession type')->constrained()->cascadeOnDelete();
            $table->text('value')->comment('the value, normalised by the kind of the field');
            $table->timestamps();

            $table->unique(['possession_id', 'possession_field_id']);
        });
    }
};
