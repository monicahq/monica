<?php

declare(strict_types=1);

use App\Models\Account;
use App\Models\Location;
use App\Models\Possession;
use App\Models\Vault;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('possession_location_stays', function (Blueprint $table): void {
            $table->id()->comment('primary key');
            $table->uuid('uuid')->unique()->comment('identifier used in addresses and by the API');
            $table->foreignIdFor(Account::class)->comment('account of the possession')->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Vault::class)->comment('vault of the possession')->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Possession::class)->comment('possession the stay belongs to')->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Location::class)->comment('location of the same account where the possession stayed; its deletion is refused while a stay refers to it')->constrained()->cascadeOnDelete();
            $table->date('arrived_on')->nullable()->comment('day it arrived, null when unknown');
            $table->date('left_on')->nullable()->comment('day it left, null on the open stay, where it is now');
            $table->string('reason')->nullable()->comment('why it went there');
            $table->text('note')->nullable()->comment('anything worth knowing about the stay');
            $table->timestamps();

            $table->index(['possession_id', 'left_on']);
        });

        $now = now();

        DB::table('possessions')
            ->whereNotNull('current_location_id')
            ->select(['id', 'account_id', 'vault_id', 'current_location_id'])
            ->orderBy('id')
            ->chunk(500, function ($possessions) use ($now): void {
                $rows = [];

                foreach ($possessions as $possession) {
                    $rows[] = [
                        'uuid' => (string) Str::uuid(),
                        'account_id' => $possession->account_id,
                        'vault_id' => $possession->vault_id,
                        'possession_id' => $possession->id,
                        'location_id' => $possession->current_location_id,
                        'arrived_on' => null,
                        'left_on' => null,
                        'reason' => null,
                        'note' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                if ($rows !== []) {
                    DB::table('possession_location_stays')->insert($rows);
                }
            });
    }
};
