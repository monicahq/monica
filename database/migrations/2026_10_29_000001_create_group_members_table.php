<?php

declare(strict_types=1);

use App\Models\Account;
use App\Models\Contact;
use App\Models\Group;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('group_members', function (Blueprint $table): void {
            $table->id()->comment('primary key');
            $table->uuid()->unique()->comment('unguessable identifier used by the API');
            $table->foreignIdFor(Account::class)->comment('account the membership belongs to')->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Group::class)->comment('group the contact belongs to')->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Contact::class)->comment('contact who is or was a member')->constrained()->cascadeOnDelete();
            $table->string('role')->nullable()->comment('what the contact is in the group, in free text');
            $table->date('joined_on')->nullable()->comment('when the contact joined, null when unknown');
            $table->string('joined_precision', 16)->default('unknown')->comment('how precisely the join is known, a DatePrecisionEnum value');
            $table->date('left_on')->nullable()->comment('when the contact left, null while they are a member');
            $table->string('left_precision', 16)->default('unknown')->comment('how precisely the departure is known, a DatePrecisionEnum value');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['group_id', 'contact_id', 'left_on']);
            $table->index(['contact_id', 'deleted_at']);
        });
    }
};
