<?php

use App\Models\Contact;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
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
            $table->unsignedBigInteger('gender_id')->nullable();
            $table->unsignedBigInteger('pronoun_id')->nullable();
            $table->unsignedBigInteger('template_id')->nullable();
            $table->unsignedBigInteger('company_id')->nullable();
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
            $table->string('distant_etag', 256)->nullable();

            $table->datetime('last_updated_at')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('gender_id')->references('id')->on('genders')->onDelete('set null');
            $table->foreign('pronoun_id')->references('id')->on('pronouns')->onDelete('set null');
            $table->foreign('template_id')->references('id')->on('templates')->onDelete('set null');
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('set null');

            $table->index(['vault_id', 'id']);

            if (config('scout.driver') === 'database' && in_array(DB::connection()->getDriverName(), ['mysql', 'pgsql'])) {
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
        Schema::dropIfExists('contacts');
        Schema::dropIfExists('user_vault');
        Schema::dropIfExists('contact_vault_user');
    }
};
