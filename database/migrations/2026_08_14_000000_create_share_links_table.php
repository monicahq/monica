<?php

declare(strict_types=1);

use App\Models\Account;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('share_links', function (Blueprint $table): void {
            $table->id()->comment('primary key');
            $table->uuid()->unique()->comment('unguessable identifier of the link itself, never the secret token in the URL');
            $table->foreignIdFor(Account::class)->comment('account that created the link')->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Vault::class)->comment('vault of the contact being shared')->constrained()->cascadeOnDelete();
            $table->string('shareable_type')->comment('class of what is shared, a contact');
            $table->unsignedBigInteger('shareable_id')->comment('id of that object');
            $table->string('token', 64)->unique()->comment('hash of the unguessable token in the URL, the token itself never stored');
            $table->string('label')->comment('name the owner gave the link, so several links are tellable apart');
            $table->string('recipient_hint')->nullable()->comment('who it was meant for, for the owner own memory');
            $table->string('password_hash')->nullable()->comment('hash of the password protecting it, null when there is none');
            $table->timestamp('expires_at')->nullable()->comment('when it stops working, null when it never does');
            $table->timestamp('revoked_at')->nullable()->comment('when it was revoked, which is immediate and permanent');
            $table->boolean('includes_files')->default(false)->comment('whether attached files are reachable through it');
            $table->boolean('includes_life_events')->default(false)->comment('whether life events appear, false by default');
            $table->boolean('includes_life_event_narrative')->default(false)->comment('whether the free account of a life event appears, false by default');
            $table->boolean('includes_day_narrative')->default(false)->comment('whether the account of a day appears, false by default');
            $table->boolean('includes_relationships')->default(false)->comment('whether relationships appear');
            $table->boolean('includes_groups')->default(false)->comment('whether group memberships appear');
            $table->boolean('includes_sensitive_fields')->default(false)->comment('whether fields marked sensitive appear, false by default');
            $table->json('excluded_section_ids')->comment('sections held back from the shared page');
            $table->json('excluded_life_event_ids')->comment('life events held back individually, tightening the general setting');
            $table->unsignedInteger('view_count')->default(0)->comment('how many times it was opened');
            $table->timestamp('last_viewed_at')->nullable()->comment('when it was last opened');
            $table->foreignIdFor(User::class, 'created_by_user_id')->nullable()->comment('user who created it')->constrained()->nullOnDelete();
            $table->timestamps();

            $table->index(['shareable_type', 'shareable_id'], 'share_links_shareable_index');
            $table->index(['vault_id', 'revoked_at']);
        });
    }
};
