<?php

declare(strict_types=1);

use App\Models\Account;
use App\Models\Field;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('field_value_rows', function (Blueprint $table): void {
            $table->id()->comment('primary key');
            $table->foreignIdFor(Account::class)->comment('account the row belongs to')->constrained()->cascadeOnDelete();
            $table->string('valuable_type')->comment('class of the record the row belongs to, a contact, an entity or a life event');
            $table->unsignedBigInteger('valuable_id')->comment('id of that record');
            $table->foreignIdFor(Field::class)->comment('repeater field this is a repetition of')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('position')->comment('order the repetition appears in, which the user can reorder');
            $table->timestamps();

            $table->index(['valuable_type', 'valuable_id', 'field_id', 'position'], 'field_value_rows_record_field_index');
        });
    }
};
