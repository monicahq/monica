<?php

use App\Models\Account;
use App\Models\AddressType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasColumn('address_types', 'type')) {
            return;
        }

        $addresses = collect([
            '🏡 Home' => 'home',
            '🏠 Secondary residence' => 'secondary',
            '🏢 Work' => 'work',
            '🌳 Chalet' => 'chalet',
        ]);

        $other = [
            'type' => 'other',
            'label' => trans_key('❔ Other'),
        ];

        Schema::table('address_types', function (Blueprint $table) {
            $table->string('type')->nullable()->after('name');
        });

        DB::table('address_types')->chunkById(100, function ($addressTypes) use ($addresses) {
            foreach ($addressTypes as $addressType) {
                $address = $addresses[$addressType->name_translation_key] ?? null;

                if ($address && $addressType->type === null) {
                    DB::table('address_types')->where('id', $addressType->id)->update([
                        'type' => $address,
                    ]);
                }
            }
        });

        Account::chunk(100, function ($accounts) use ($other) {
            foreach ($accounts as $account) {
                AddressType::create([
                    'account_id' => $account->id,
                    'name_translation_key' => $other['label'],
                    'type' => $other['type'],
                ]);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('address_types', function (Blueprint $table) {
            $table->dropColumn('type');
        });

        Account::chunk(100, function ($accounts) {
            foreach ($accounts as $account) {
                AddressType::where('account_id', $account->id)
                    ->where('name_translation_key', trans_key('❔ Other'))
                    ->delete();
            }
        });
    }
};
