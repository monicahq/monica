<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fields', function (Blueprint $table): void {
            $table->id()->comment('primary key');
            $table->unsignedBigInteger('account_id')->comment('account the field belongs to');
            $table->unsignedBigInteger('section_version_id')->comment('section version the field belongs to');
            $table->string('type', 32)->comment('what kind of field it is, from the catalogue');
            $table->string('key', 64)->comment('stable identifier within the section, used by the API and by imports');
            $table->string('label')->nullable()->comment('label shown next to the input, null when it falls back to its translation key');
            $table->string('label_translation_key')->nullable()->comment('translation key of a supplied label');
            $table->string('help_text')->nullable()->comment('clarification shown under the input');
            $table->string('placeholder')->nullable()->comment('hint shown inside an empty input');
            $table->unsignedInteger('position')->comment('order the field appears in inside the section');
            $table->boolean('is_required')->default(false)->comment('whether a value must be given before the record can be saved');
            $table->boolean('is_sensitive')->default(false)->comment('whether the value is masked until revealed, and excluded from share links');
            $table->boolean('is_highlighted')->default(false)->comment('whether the field also appears in the header area of the record');
            $table->boolean('is_unique')->default(false)->comment('whether two records in the vault may not hold the same value');
            $table->unsignedBigInteger('value_list_id')->nullable()->comment('list the choices come from, for the choice types, null otherwise');
            $table->json('default_value')->nullable()->comment('value a new record starts with, null when it starts empty');
            $table->json('options')->nullable()->comment('settings specific to the type, such as min, max, decimals, accepted mime types');
            $table->json('validation')->nullable()->comment('extra constraints, expressed so the API and the interface enforce the same ones');
            $table->boolean('is_repeatable')->default(false)->comment('whether the field is a repeater, letting the same section be filled several times');
            $table->unsignedInteger('repeat_min')->nullable()->comment('fewest repetitions allowed, null for no floor');
            $table->unsignedInteger('repeat_max')->nullable()->comment('most repetitions allowed, null for no ceiling');
            $table->string('repetition_order', 32)->nullable()->comment('which end a new repetition is added at, a RepetitionOrderEnum value, null means the end and means nothing for a field that is not a repeater');
            $table->unsignedBigInteger('parent_field_id')->nullable()->comment('repeater this field sits inside, null when it sits directly in the section');
            $table->string('relation_target', 32)->nullable()->comment('what a relation field points at, null for other types');
            $table->unsignedBigInteger('relation_entity_type_id')->nullable()->comment('entity type a relation field points at, null when it points at contacts');
            $table->timestamps();

            $table->unique(['section_version_id', 'key']);

            $table->foreign('account_id')->references('id')->on('accounts')->cascadeOnDelete();
            $table->foreign('section_version_id')->references('id')->on('section_versions')->cascadeOnDelete();
            $table->foreign('value_list_id')->references('id')->on('value_lists')->nullOnDelete();
            $table->foreign('parent_field_id')->references('id')->on('fields')->cascadeOnDelete();
            $table->foreign('relation_entity_type_id')->references('id')->on('entity_types')->nullOnDelete();
        });
    }
};
