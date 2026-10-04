<?php

declare(strict_types=1);

use App\Models\Account;
use App\Models\RelationshipCategory;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('relationship_types', function (Blueprint $table): void {
            $table->id()->comment('primary key');
            $table->uuid()->unique()->comment('unguessable identifier used in URLs and by the API');
            $table->foreignIdFor(Account::class)->comment('account the type belongs to')->constrained()->cascadeOnDelete();
            $table->foreignIdFor(RelationshipCategory::class)->comment('display category containing the type')->constrained()->cascadeOnDelete();
            $table->string('key', 64)->nullable()->comment('stable identifier of a supplied type, null for a user-created one');
            $table->string('name')->nullable()->comment('what the user called it, null while the supplied wording stands');
            $table->string('name_translation_key')->nullable()->comment('the supplied wording of the name');
            $table->string('role_a')->nullable()->comment('how A relates to B, such as Parent');
            $table->string('role_a_translation_key')->nullable()->comment('the supplied wording of the first role');
            $table->string('role_b')->nullable()->comment('how B relates to A, such as Child');
            $table->string('role_b_translation_key')->nullable()->comment('the supplied wording of the opposite role');
            $table->boolean('is_symmetric')->comment('whether both directions use the same role');
            $table->string('system_behavior', 32)->nullable()->comment('protected product behaviour, null for ordinary types');
            $table->boolean('is_system')->default(false)->comment('whether the product supplied it with the account');
            $table->boolean('is_active')->default(true)->comment('whether it can be chosen for a new relationship');
            $table->unsignedInteger('position')->comment('order inside its category');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['account_id', 'key']);
        });
    }
};
