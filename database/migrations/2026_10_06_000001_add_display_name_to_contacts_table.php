<?php

declare(strict_types=1);

use App\Models\ContactNameRepresentation;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contacts', function (Blueprint $table): void {
            $table->foreignIdFor(ContactNameRepresentation::class, 'display_name_representation_id')->nullable()->after('suffix')->comment('the representation of the name lists, titles and initials show, null when the contact has no name')->constrained()->nullOnDelete();
            $table->string('formatted_name')->nullable()->after('display_name_representation_id')->comment('copy of the display representation formatted name, written only by the model');
        });
    }
};
