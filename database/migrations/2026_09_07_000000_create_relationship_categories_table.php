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
        Schema::create('relationship_categories', function (Blueprint $table): void {
            $table->id()->comment('primary key');
            $table->uuid()->unique()->comment('unguessable identifier used in URLs and by the API');
            $table->foreignIdFor(Account::class)->comment('account the category belongs to')->constrained()->cascadeOnDelete();
            $table->string('key', 64)->nullable()->comment('stable identifier of a supplied category, null for a user-created one');
            $table->string('name')->nullable()->comment('what the user called it, null while the supplied wording stands');
            $table->string('name_translation_key')->nullable()->comment('the supplied wording, read through the translator');
            $table->boolean('is_system')->default(false)->comment('whether the product supplied it with the account');
            $table->boolean('is_active')->default(true)->comment('whether it is shown when choosing a type for a new relationship');
            $table->unsignedInteger('position')->comment('order it appears in settings and pickers');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['account_id', 'key']);
        });
    }
};
