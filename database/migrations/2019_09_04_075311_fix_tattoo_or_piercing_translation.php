<?php

use App\Models\Account\Account;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Migrations\Migration;

class FixTattooOrPiercingTranslation extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $appLocale = config('app.locale');

        Account::chunk(200, function ($accounts) use ($appLocale) {
            foreach ($accounts as $account) {
                $locale = $account->getFirstLocale() ?: $appLocale;

                DB::table('life_event_types')
                ->where([
                    'account_id' => $account->id,
                    'default_life_event_type_key' => 'tattoo_or_piercing',
                ])
                ->update([
                    'name' => trans('settings.personalization_life_event_type_tattoo_or_piercing', [], $locale),
                ]);
            }
        });
    }
}
