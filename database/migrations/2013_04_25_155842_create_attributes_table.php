<?php

use App\Models\Account;
use App\Models\Module;
use App\Models\ModuleRow;
use App\Models\Template;
use App\Models\TemplatePage;
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
        Schema::create('templates', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Account::class)->constrained()->cascadeOnDelete();
            $table->string('name')->nullable();
            $table->string('name_translation_key')->nullable();
            $table->timestamps();
        });

        Schema::create('template_pages', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Template::class)->constrained()->cascadeOnDelete();
            $table->string('name')->nullable();
            $table->string('name_translation_key')->nullable();
            $table->string('slug');
            $table->integer('position')->nullable();
            $table->string('type')->nullable();
            $table->boolean('can_be_deleted')->default(true);
            $table->timestamps();
        });

        Schema::create('modules', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Account::class)->constrained()->cascadeOnDelete();
            $table->string('name')->nullable();
            $table->string('name_translation_key')->nullable();
            $table->string('type')->nullable();
            $table->boolean('reserved_to_contact_information')->default(false);
            $table->boolean('can_be_deleted')->default(true);
            $table->integer('pagination')->nullable();
            $table->timestamps();
        });

        Schema::create('module_template_page', function (Blueprint $table) {
            $table->foreignIdFor(TemplatePage::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Module::class)->constrained()->cascadeOnDelete();
            $table->integer('position')->nullable();
            $table->timestamps();
        });

        Schema::create('module_rows', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Module::class)->constrained()->cascadeOnDelete();
            $table->integer('position')->nullable();
            $table->timestamps();
        });

        Schema::create('module_row_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(ModuleRow::class)->constrained()->cascadeOnDelete();
            $table->string('label');
            $table->string('module_field_type');
            $table->boolean('required')->default(false);
            $table->integer('position')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('module_row_fields');
        Schema::dropIfExists('module_rows');
        Schema::dropIfExists('module_template_page');
        Schema::dropIfExists('modules');
        Schema::dropIfExists('template_pages');
        Schema::dropIfExists('templates');
    }
};
