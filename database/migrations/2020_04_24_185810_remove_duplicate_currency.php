<?php

use App\Models\User\User;
use App\Models\Settings\Currency;
use Illuminate\Database\Migrations\Migration;

class RemoveDuplicateCurrency extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $doubleCurrency = DB::table('currencies')
            ->select(DB::raw('max(id) as id'), 'iso')
            ->groupBy('iso')
            ->havingRaw('count(*) > ?', [1])
            ->get();

        foreach ($doubleCurrency as $currency) {
            $newCurrency = Currency::where('iso', $currency->iso)->first();

            User::where('currency_id', $currency->id)->chunk(500, function ($users) use ($newCurrency) {
                foreach ($users as $user) {
                    $user->update([
                        'currency_id' => $newCurrency->id,
                    ]);
                }
            });

            Currency::find($currency->id)->delete();
        }
    }
}
