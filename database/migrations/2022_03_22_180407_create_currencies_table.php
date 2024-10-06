<?php

use App\Models\Account;
use App\Models\Currency;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('currencies', function (Blueprint $table) {
            $table->id();
            $table->string('code');
            $table->timestamps();
        });

        Schema::create('account_currency', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Currency::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Account::class)->constrained()->cascadeOnDelete();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        DB::table('currencies')->insert([
            ['code' => 'AFA'],
            ['code' => 'ALL'],
            ['code' => 'DZD'],
            ['code' => 'AOA'],
            ['code' => 'ARS'],
            ['code' => 'AMD'],
            ['code' => 'AWG'],
            ['code' => 'AUD'],
            ['code' => 'AZN'],
            ['code' => 'BSD'],
            ['code' => 'BHD'],
            ['code' => 'BDT'],
            ['code' => 'BBD'],
            ['code' => 'BYR'],
            ['code' => 'BEF'],
            ['code' => 'BZD'],
            ['code' => 'BMD'],
            ['code' => 'BTN'],
            ['code' => 'BTC'],
            ['code' => 'BOB'],
            ['code' => 'BAM'],
            ['code' => 'BWP'],
            ['code' => 'BRL'],
            ['code' => 'GBP'],
            ['code' => 'BND'],
            ['code' => 'BGN'],
            ['code' => 'BIF'],
            ['code' => 'KHR'],
            ['code' => 'CAD'],
            ['code' => 'CVE'],
            ['code' => 'KYD'],
            ['code' => 'XOF'],
            ['code' => 'XAF'],
            ['code' => 'XPF'],
            ['code' => 'CLP'],
            ['code' => 'CNY'],
            ['code' => 'COP'],
            ['code' => 'KMF'],
            ['code' => 'CDF'],
            ['code' => 'CRC'],
            ['code' => 'HRK'],
            ['code' => 'CUC'],
            ['code' => 'CZK'],
            ['code' => 'DKK'],
            ['code' => 'DJF'],
            ['code' => 'DOP'],
            ['code' => 'XCD'],
            ['code' => 'EGP'],
            ['code' => 'ERN'],
            ['code' => 'EEK'],
            ['code' => 'ETB'],
            ['code' => 'EUR'],
            ['code' => 'FKP'],
            ['code' => 'FJD'],
            ['code' => 'GMD'],
            ['code' => 'GEL'],
            ['code' => 'DEM'],
            ['code' => 'GHS'],
            ['code' => 'GIP'],
            ['code' => 'GRD'],
            ['code' => 'GTQ'],
            ['code' => 'GNF'],
            ['code' => 'GYD'],
            ['code' => 'HTG'],
            ['code' => 'HNL'],
            ['code' => 'HKD'],
            ['code' => 'HUF'],
            ['code' => 'ISK'],
            ['code' => 'INR'],
            ['code' => 'IDR'],
            ['code' => 'IRR'],
            ['code' => 'IQD'],
            ['code' => 'ILS'],
            ['code' => 'ITL'],
            ['code' => 'JMD'],
            ['code' => 'JPY'],
            ['code' => 'JOD'],
            ['code' => 'KZT'],
            ['code' => 'KES'],
            ['code' => 'KWD'],
            ['code' => 'KGS'],
            ['code' => 'LAK'],
            ['code' => 'LVL'],
            ['code' => 'LBP'],
            ['code' => 'LSL'],
            ['code' => 'LRD'],
            ['code' => 'LYD'],
            ['code' => 'LTL'],
            ['code' => 'MOP'],
            ['code' => 'MKD'],
            ['code' => 'MGA'],
            ['code' => 'MWK'],
            ['code' => 'MYR'],
            ['code' => 'MVR'],
            ['code' => 'MRO'],
            ['code' => 'MUR'],
            ['code' => 'MXN'],
            ['code' => 'MDL'],
            ['code' => 'MNT'],
            ['code' => 'MAD'],
            ['code' => 'MZM'],
            ['code' => 'MMK'],
            ['code' => 'NAD'],
            ['code' => 'NPR'],
            ['code' => 'ANG'],
            ['code' => 'TWD'],
            ['code' => 'NZD'],
            ['code' => 'NIO'],
            ['code' => 'NGN'],
            ['code' => 'KPW'],
            ['code' => 'NOK'],
            ['code' => 'OMR'],
            ['code' => 'PKR'],
            ['code' => 'PAB'],
            ['code' => 'PGK'],
            ['code' => 'PYG'],
            ['code' => 'PEN'],
            ['code' => 'PHP'],
            ['code' => 'PLN'],
            ['code' => 'QAR'],
            ['code' => 'RON'],
            ['code' => 'RUB'],
            ['code' => 'RWF'],
            ['code' => 'SVC'],
            ['code' => 'WST'],
            ['code' => 'SAR'],
            ['code' => 'RSD'],
            ['code' => 'SCR'],
            ['code' => 'SLL'],
            ['code' => 'SGD'],
            ['code' => 'SKK'],
            ['code' => 'SBD'],
            ['code' => 'SOS'],
            ['code' => 'ZAR'],
            ['code' => 'KRW'],
            ['code' => 'XDR'],
            ['code' => 'LKR'],
            ['code' => 'SHP'],
            ['code' => 'SDG'],
            ['code' => 'SRD'],
            ['code' => 'SZL'],
            ['code' => 'SEK'],
            ['code' => 'CHF'],
            ['code' => 'SYP'],
            ['code' => 'STD'],
            ['code' => 'TJS'],
            ['code' => 'TZS'],
            ['code' => 'THB'],
            ['code' => 'TOP'],
            ['code' => 'TTD'],
            ['code' => 'TND'],
            ['code' => 'TRY'],
            ['code' => 'TMT'],
            ['code' => 'UGX'],
            ['code' => 'UAH'],
            ['code' => 'AED'],
            ['code' => 'UYU'],
            ['code' => 'USD'],
            ['code' => 'UZS'],
            ['code' => 'VUV'],
            ['code' => 'VEF'],
            ['code' => 'VND'],
            ['code' => 'YER'],
            ['code' => 'ZM'],
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('account_currency');
        Schema::dropIfExists('currencies');
    }
};
