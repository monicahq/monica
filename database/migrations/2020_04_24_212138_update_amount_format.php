<?php

use App\Helpers\MoneyHelper;
use App\Models\Account\Account;
use Illuminate\Support\Facades\DB;
use Money\Currencies\ISOCurrencies;
use Money\Currency as MoneyCurrency;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class UpdateAmountFormat extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $this->fixDebts();
        $this->fixGifts();
    }

    private function fixDebts()
    {
        Schema::table('debts', function (Blueprint $table) {
            $table->decimal('amount', 13, 2)->change();
            $table->unsignedInteger('currency_id')->after('amount')->nullable();
            $table->foreign('currency_id')->references('id')->on('currencies')->onDelete('set null');
        });

        DB::table('debts')
            ->orderBy('id')
            ->chunk(500, function ($debts) {
                foreach ($debts as $debt) {
                    try {
                        $account = Account::findOrFail($debt->account_id);
                        $user = $account->users()->firstOrFail();
                    } catch (ModelNotFoundException $e) {
                        continue;
                    }

                    DB::update('update debts set amount = ?, currency_id = ? where id = ?', [
                        floatval($debt->amount) * self::unitAdjustment($user->currency),
                        $user->currency_id,
                        $debt->id,
                    ]);
                }
            });

        Schema::table('debts', function (Blueprint $table) {
            $table->integer('amount')->change();
        });
    }

    private function fixGifts()
    {
        Schema::table('gifts', function (Blueprint $table) {
            $table->unsignedInteger('currency_id')->after('value')->nullable();
            $table->foreign('currency_id')->references('id')->on('currencies')->onDelete('set null');
        });

        DB::table('gifts')
            ->where('value', '!=', 'null')
            ->orderBy('id')
            ->chunk(500, function ($gifts) {
                foreach ($gifts as $gift) {
                    try {
                        $account = Account::findOrFail($gift->account_id);
                        $user = $account->users()->firstOrFail();
                    } catch (ModelNotFoundException $e) {
                        continue;
                    }

                    DB::update('update gifts set value = ?, currency_id = ? where id = ?', [
                        floatval($gift->value) * self::unitAdjustment($user->currency),
                        $user->currency_id,
                        $gift->id,
                    ]);
                }
            });

        Schema::table('gifts', function (Blueprint $table) {
            $table->integer('value')->change()->nullable();
            $table->renameColumn('value', 'amount');
        });
    }

    /**
     * Get unit adjustement value for the currency.
     *
     * @param  \App\Models\Settings\Currency|int|null  $currency
     * @return int
     */
    private static function unitAdjustment($currency): int
    {
        $currency = MoneyHelper::getCurrency($currency);

        if (! $currency) {
            return 100;
        }

        $moneyCurrency = new MoneyCurrency($currency->iso);
        $currencies = new ISOCurrencies();

        return (int) pow(10, $currencies->subunitFor($moneyCurrency));
    }
}
