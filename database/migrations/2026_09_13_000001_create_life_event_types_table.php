<?php

declare(strict_types=1);

use App\Models\Account;
use App\Models\LifeEventCategory;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('life_event_types', function (Blueprint $table): void {
            $table->id()->comment('primary key');
            $table->uuid()->unique()->comment('unguessable identifier used in URLs and by the API');
            $table->foreignIdFor(Account::class)->comment('account the type belongs to')->constrained()->cascadeOnDelete();
            $table->foreignIdFor(LifeEventCategory::class)->comment('category the type is filed under')->constrained()->cascadeOnDelete();
            $table->string('key', 64)->nullable()->comment('stable identifier of a supplied type, null for a user-created one');
            $table->string('name')->nullable()->comment('what the user called it, null while the supplied wording stands');
            $table->string('name_translation_key')->nullable()->comment('the supplied wording, read through the translator');
            $table->string('description')->nullable()->comment('what it covers, shown when picking a type');
            $table->string('icon', 64)->nullable()->comment('icon shown on its life events, with a neutral fallback');
            $table->string('colour', 7)->nullable()->comment('colour used the same way, with a neutral fallback');
            $table->boolean('is_system')->default(false)->comment('whether the product supplied it, which governs deletion only');
            $table->boolean('is_active')->default(true)->comment('whether it can still be chosen for a new life event');
            $table->unsignedInteger('position')->comment('order it appears in the picker, inside its category');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['account_id', 'key']);
        });
    }
};
