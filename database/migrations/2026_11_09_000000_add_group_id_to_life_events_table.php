<?php

declare(strict_types=1);

use App\Models\Group;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('life_events', function (Blueprint $table): void {
            $table->foreignIdFor(Group::class)->nullable()->after('structure_version_id')->comment('group the life event was recorded on, null for every other life event')->constrained()->nullOnDelete();

            $table->index(['group_id', 'happened_on']);
        });
    }
};
