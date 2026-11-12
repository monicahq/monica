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
        Schema::create('possession_conditions', function (Blueprint $table): void {
            $table->id()->comment('primary key');
            $table->uuid('uuid')->unique()->comment('identifier used in addresses and by the API');
            $table->foreignIdFor(Account::class)->comment('account the condition belongs to, shared by every vault of it')->constrained()->cascadeOnDelete();
            $table->string('name', 50)->comment('what the condition reads, 1 to 50 characters, unique in the account ignoring case');
            $table->string('colour', 7)->comment('one of the vault colours, as a hexadecimal value');
            $table->string('icon', 30)->nullable()->comment('one of the glyphs of the section catalogue, or none');
            $table->unsignedInteger('position')->comment('place in the account order, from best to worst, 1 to n without gaps');
            $table->timestamps();

            $table->index(['account_id', 'position']);
        });
    }
};
