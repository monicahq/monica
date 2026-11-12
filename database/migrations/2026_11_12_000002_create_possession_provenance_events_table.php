<?php

declare(strict_types=1);

use App\Models\Account;
use App\Models\Possession;
use App\Models\PossessionTransaction;
use App\Models\Vault;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('possession_provenance_events', function (Blueprint $table): void {
            $table->id()->comment('primary key');
            $table->uuid('uuid')->unique()->comment('identifier used in addresses and by the API');
            $table->foreignIdFor(Account::class)->comment('account of the possession')->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Vault::class)->comment('vault of the possession')->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Possession::class)->comment('possession the event belongs to')->constrained()->cascadeOnDelete();
            $table->foreignIdFor(PossessionTransaction::class)->nullable()->comment('transaction of the same possession that records what the moment cost')->constrained()->nullOnDelete();
            $table->string('type')->comment('origin, acquisition, inheritance, gift, sale, custody, loss, recovery, authentication, exhibition, publication or other');
            $table->string('title')->nullable()->comment('what happened; the name of the type is shown when empty');
            $table->string('date_precision')->default('day')->comment('day, month, year, circa or unknown');
            $table->date('occurred_on')->nullable()->comment('when it happened; the first of the month or of the year when that is all that is known');
            $table->string('from')->nullable()->comment('who it came from, as text');
            $table->string('to')->nullable()->comment('who held it afterwards, as text');
            $table->string('location')->nullable()->comment('where it happened, as text');
            $table->text('description')->nullable()->comment('what happened, and how it is known');
            $table->string('reference_number')->nullable()->comment('reference of the record: a lot, a deed, a certificate');
            $table->string('source_url')->nullable()->comment('where the event can be checked');
            $table->boolean('is_verified')->default(false)->comment('whether a document, a record or an expert confirms it');
            $table->timestamps();

            $table->index(['possession_id', 'occurred_on']);
        });
    }
};
