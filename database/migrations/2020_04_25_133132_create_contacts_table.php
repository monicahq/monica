<?php

use App\Helpers\ScoutHelper;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Gender;
use App\Models\Pronoun;
use App\Models\Template;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('contacts', function (Blueprint $table) {
            $table->uuid('id');
            $table->primary('id');
            $table->foreignIdFor(Vault::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Gender::class)->nullable()->constrained()->nullOnDelete();
            $table->foreignIdFor(Pronoun::class)->nullable()->constrained()->nullOnDelete();
            $table->foreignIdFor(Template::class)->nullable()->constrained()->nullOnDelete();
            $table->foreignIdFor(Company::class)->nullable()->constrained()->nullOnDelete();
            $table->string('first_name')->nullable();
            $table->string('middle_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('nickname')->nullable();
            $table->string('maiden_name')->nullable();
            $table->string('suffix')->nullable();
            $table->string('prefix')->nullable();
            $table->string('job_position')->nullable();
            $table->boolean('can_be_deleted')->default(true);
            $table->boolean('listed')->default(true);

            $table->mediumText('vcard')->nullable();
            $table->string('distant_uuid', 256)->nullable();
            $table->string('distant_etag', 256)->nullable();
            $table->string('distant_uri', 2096)->nullable();

            $table->datetime('last_updated_at')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['vault_id', 'id']);

            if (ScoutHelper::isFullTextIndex()) {
                $table->fullText('first_name');
                $table->fullText('last_name');
                $table->fullText('middle_name');
                $table->fullText('nickname');
                $table->fullText('maiden_name');
            }
        });

        Schema::create('user_vault', function (Blueprint $table) {
            $table->foreignIdFor(Vault::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(User::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Contact::class)->constrained()->cascadeOnDelete();
            $table->integer('permission');
            $table->timestamps();
        });

        Schema::create('contact_vault_user', function (Blueprint $table) {
            $table->foreignIdFor(Contact::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Vault::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(User::class)->constrained()->cascadeOnDelete();
            $table->integer('number_of_views');
            $table->boolean('is_favorite')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('contact_vault_user');
        Schema::dropIfExists('user_vault');
        Schema::dropIfExists('contacts');
    }
};
