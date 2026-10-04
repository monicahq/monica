<?php

declare(strict_types=1);

use App\Models\Account;
use App\Models\Contact;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contact_duplicate_candidates', function (Blueprint $table): void {
            $table->id()->comment('primary key');
            $table->foreignIdFor(Account::class)->comment('account the pair belongs to')->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Vault::class)->comment('vault both contacts live in')->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Contact::class)->comment('lower of the two ids, so one pair is one row whichever side it was found from')->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Contact::class, 'other_contact_id')->comment('higher of the two ids')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('score')->comment('how strong the resemblance is, 0 to 100');
            $table->json('reasons')->comment('one entry per signal that fired, each naming its kind and, for an identifying field, the field key and the shared value');
            $table->timestamp('dismissed_at')->nullable()->comment('when somebody said these are two different people, null while the pair stands');
            $table->foreignIdFor(User::class, 'dismissed_by_user_id')->nullable()->comment('user who said so')->constrained()->nullOnDelete();
            $table->timestamp('computed_at')->comment('when detection last looked at this pair');
            $table->timestamps();

            $table->unique(['contact_id', 'other_contact_id'], 'contact_duplicate_candidates_pair_unique');
            $table->index(['vault_id', 'dismissed_at', 'score'], 'contact_duplicate_candidates_screen_index');
        });
    }
};
