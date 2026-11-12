<?php

declare(strict_types=1);

use App\Models\Account;
use App\Models\PossessionFieldGroup;
use App\Models\PossessionType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('possession_types', function (Blueprint $table): void {
            $table->id()->comment('primary key');
            $table->uuid('uuid')->unique()->comment('identifier used in addresses and by the API');
            $table->foreignIdFor(Account::class)->comment('account the type belongs to, shared by every vault of it')->constrained()->cascadeOnDelete();
            $table->string('name')->comment('what the kind of thing is called');
            $table->string('colour', 7)->comment('one of the vault colours, as a hexadecimal value');
            $table->string('icon', 30)->nullable()->comment('one of the glyphs of the section catalogue, or none');
            $table->timestamps();
        });

        Schema::create('possession_field_groups', function (Blueprint $table): void {
            $table->id()->comment('primary key');
            $table->uuid('uuid')->unique()->comment('identifier used in addresses and by the API');
            $table->foreignIdFor(Account::class)->comment('account the group belongs to, always the account of its type')->constrained()->cascadeOnDelete();
            $table->foreignIdFor(PossessionType::class)->comment('type the group belongs to')->constrained()->cascadeOnDelete();
            $table->string('name')->comment('what the group is called');
            $table->unsignedInteger('position')->comment('order among the groups of the type, from 1');
            $table->timestamps();

            $table->index(['possession_type_id', 'position']);
        });

        Schema::create('possession_fields', function (Blueprint $table): void {
            $table->id()->comment('primary key');
            $table->uuid('uuid')->unique()->comment('identifier used in addresses and by the API');
            $table->foreignIdFor(Account::class)->comment('account the field belongs to, always the account of its type')->constrained()->cascadeOnDelete();
            $table->foreignIdFor(PossessionType::class)->comment('type the field belongs to')->constrained()->cascadeOnDelete();
            $table->foreignIdFor(PossessionFieldGroup::class)->nullable()->comment('group the field sits in, null when it stands alone, set when the field is created')->constrained()->nullOnDelete();
            $table->string('name')->comment('what the field is called');
            $table->string('kind')->comment('text, number, date, boolean, select or rating');
            $table->json('options')->nullable()->comment('the choices of a select field, in order, null for every other kind');
            $table->unsignedInteger('position')->comment('order within its group, or among the standalone fields of the type, from 1');
            $table->timestamps();

            $table->index(['possession_type_id', 'position']);
        });
    }
};
