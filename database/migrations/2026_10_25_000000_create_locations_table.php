<?php

declare(strict_types=1);

use App\Models\Account;
use App\Models\Location;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('locations', function (Blueprint $table): void {
            $table->id()->comment('primary key');
            $table->uuid('uuid')->unique()->comment('identifier used in addresses, and by the API later');
            $table->foreignIdFor(Account::class)->comment('account the location belongs to, shared by every vault of it')->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Location::class, 'parent_id')->nullable()->comment('location it sits inside, null at the top level, always in the same account')->constrained('locations')->cascadeOnDelete();
            $table->string('name')->comment('what the location is called');
            $table->string('emoji', 32)->nullable()->comment('one emoji shown in place of the default icon');
            $table->text('address')->nullable()->comment('where it is, as the user wrote it, over several lines');
            $table->timestamps();

            $table->index(['account_id', 'parent_id']);
        });
    }
};
