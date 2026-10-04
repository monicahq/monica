<?php

declare(strict_types=1);

use App\Models\Account;
use App\Models\Contact;
use App\Models\RelationshipType;
use App\Models\Vault;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('relationships', function (Blueprint $table): void {
            $table->id()->comment('primary key');
            $table->uuid()->unique()->comment('unguessable identifier used in URLs and by the API');
            $table->foreignIdFor(Account::class)->comment('account the relationship belongs to')->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Vault::class)->comment('vault both contacts live in')->constrained()->cascadeOnDelete();
            $table->foreignIdFor(RelationshipType::class)->comment('structure of the link')->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Contact::class, 'contact_a_id')->comment('side carrying role A of a directed type')->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Contact::class, 'contact_b_id')->comment('side carrying role B of a directed type')->constrained()->cascadeOnDelete();
            $table->string('status', 32)->comment('whether the relationship applies today, a RelationshipStatusEnum value');
            $table->date('started_on')->nullable()->comment('known start date');
            $table->date('ended_on')->nullable()->comment('known end date');
            $table->text('notes')->nullable()->comment('context written by the user');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['vault_id', 'contact_a_id', 'relationship_type_id', 'deleted_at'], 'relationships_side_a_index');
            $table->index(['vault_id', 'contact_b_id', 'relationship_type_id', 'deleted_at'], 'relationships_side_b_index');
        });
    }
};
