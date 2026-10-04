<?php

declare(strict_types=1);

use App\Models\Account;
use App\Models\Contact;
use App\Models\Entity;
use App\Models\Field;
use App\Models\FieldValueRow;
use App\Models\File;
use App\Models\ValueListItem;
use App\Models\Vault;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $filterableString = in_array(Schema::getConnection()->getDriverName(), ['mysql', 'mariadb'], true)
            ? new Expression('value_string(255)')
            : 'value_string';

        Schema::create('field_values', function (Blueprint $table) use ($filterableString): void {
            $table->id()->comment('primary key');
            $table->foreignIdFor(Account::class)->comment('account the value belongs to')->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Vault::class)->comment('vault the value belongs to, so filtering never needs a join')->constrained()->cascadeOnDelete();
            $table->string('valuable_type')->comment('class of the record, a contact, an entity or a life event');
            $table->unsignedBigInteger('valuable_id')->comment('id of that record');
            $table->foreignIdFor(Field::class)->comment('field the value was entered for')->constrained()->cascadeOnDelete();
            $table->string('field_key', 64)->comment('key of that field copied at write time, so the value survives the field');
            $table->foreignIdFor(FieldValueRow::class)->nullable()->comment('repeater row it belongs to, null when it is not inside a repeater')->constrained()->cascadeOnDelete();
            $table->string('value_string', 1024)->nullable()->comment('value for the text, url, email, phone and colour types');
            $table->text('value_text')->nullable()->comment('value for the long text and rich text types');
            $table->decimal('value_number', 20, 6)->nullable()->comment('value for the number, rating and duration types');
            $table->boolean('value_boolean')->nullable()->comment('value for the yes or no type');
            $table->date('value_date')->nullable()->comment('value for the date and recurring date types');
            $table->timestamp('value_datetime')->nullable()->comment('value for a type that carries a time as well');
            $table->json('value_json')->nullable()->comment('value for the composite types, such as an address or a location');
            $table->foreignIdFor(ValueListItem::class)->nullable()->comment('chosen item for the single choice types, and the label of an email or a phone')->constrained()->nullOnDelete();
            $table->string('label_wording')->nullable()->comment('wording somebody typed for a label the list does not name, kept only when the chosen item is the open one');
            $table->json('value_list_item_ids')->nullable()->comment('chosen items for the multiple choice types');
            $table->foreignIdFor(File::class, 'value_file_id')->nullable()->comment('attached file for the file, image and avatar types')->constrained()->nullOnDelete();
            $table->foreignIdFor(Contact::class, 'related_contact_id')->nullable()->comment('target of a relation field pointing at a contact')->constrained();
            $table->foreignIdFor(Entity::class, 'related_entity_id')->nullable()->comment('target of a relation field pointing at an entity')->constrained();
            $table->boolean('year_is_unknown')->default(false)->comment('whether the year of a recurring date is not known, so it is never rendered');
            $table->boolean('month_is_unknown')->default(false)->comment('whether the month is not known either, for a date reduced to a year');
            $table->boolean('is_detached')->default(false)->comment('whether the field left the structure, hiding the value without destroying it');
            $table->timestamp('detached_at')->nullable()->comment('when that happened, so the page can explain why the value is set aside');
            $table->timestamps();

            $table->index(['valuable_type', 'valuable_id', 'field_id'], 'field_values_record_field_index');

            $table->index(['vault_id', 'field_id', $filterableString], 'field_values_filter_string_index');
            $table->index(['vault_id', 'field_id', 'value_number'], 'field_values_filter_number_index');
            $table->index(['vault_id', 'field_id', 'value_boolean'], 'field_values_filter_boolean_index');
            $table->index(['vault_id', 'field_id', 'value_date'], 'field_values_filter_date_index');
            $table->index(['vault_id', 'field_id', 'value_list_item_id'], 'field_values_filter_list_item_index');
        });
    }
};
