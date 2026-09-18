<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('instance_settings', function (Blueprint $table): void {
            $table->id()->comment('primary key');
            $table->string('name')->unique()->comment('the setting this row holds, named by InstanceSettingEnum');
            $table->text('value')->nullable()->comment('what an administrator chose, encrypted, absent while the deployment value governs');
            $table->timestamps();
        });
    }
};
