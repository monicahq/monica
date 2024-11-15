<?php

use App\Helpers\ScoutHelper;
use App\Models\Account;
use App\Models\Contact;
use App\Models\Group;
use App\Models\GroupType;
use App\Models\GroupTypeRole;
use App\Models\Vault;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('group_types', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Account::class)->constrained()->cascadeOnDelete();
            $table->string('label')->nullable();
            $table->string('label_translation_key')->nullable();
            $table->integer('position');
            $table->timestamps();
        });

        Schema::create('group_type_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(GroupType::class)->constrained()->cascadeOnDelete();
            $table->string('label')->nullable();
            $table->string('label_translation_key')->nullable();
            $table->integer('position')->nullable();
            $table->timestamps();
        });

        Schema::create('groups', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->nullable();
            $table->foreignIdFor(Vault::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(GroupType::class)->nullable()->constrained()->nullOnDelete();
            $table->string('name');

            $table->mediumText('vcard')->nullable();
            $table->string('distant_uuid', 256)->nullable();
            $table->string('distant_etag', 256)->nullable();
            $table->string('distant_uri', 2096)->nullable();

            $table->softDeletes();
            $table->timestamps();

            if (ScoutHelper::isFullTextIndex()) {
                $table->fullText('name');
            }
        });

        Schema::create('contact_group', function (Blueprint $table) {
            $table->foreignIdFor(Group::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Contact::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(GroupTypeRole::class)->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('contact_group');
        Schema::dropIfExists('groups');
        Schema::dropIfExists('group_type_roles');
        Schema::dropIfExists('group_types');
    }
};
