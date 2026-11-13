<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('share_links', function (Blueprint $table): void {
            $table->json('included_section_ids')->nullable()->after('includes_sensitive_fields')->comment('ids of the sections shown on the shared page, so a section stays shown or hidden whichever template version the contact is on');
        });
    }
};
