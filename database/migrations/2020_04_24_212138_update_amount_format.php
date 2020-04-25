<?php

use App\Helpers\MoneyHelper;
use App\Models\Contact\Debt;
use App\Models\Contact\Gift;
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

        Debt::chunk(500, function ($debts) {
            foreach ($debts as $debt) {
                try {
                    $user = $debt->account->users()->firstOrFail();
                } catch (ModelNotFoundException $e) {
                    continue;
                }

                $debt->update([
                    'amount' => $debt->amount * MoneyHelper::unitAdjustment($user->currency),
                    'currency_id' => $user->currency_id,
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

        Gift::where('value', '!=', 'null')->chunk(500, function ($gifts) {
            foreach ($gifts as $gift) {
                try {
                    $user = $gift->account->users()->firstOrFail();
                } catch (ModelNotFoundException $e) {
                    continue;
                }

                $gift->update([
                    'value' => $gift->value * MoneyHelper::unitAdjustment($user->currency),
                    'currency_id' => $user->currency_id,
                ]);
            }
        });

        Schema::table('gifts', function (Blueprint $table) {
            $table->integer('value')->change()->nullable();
            $table->renameColumn('value', 'amount');
        });
    }
}
