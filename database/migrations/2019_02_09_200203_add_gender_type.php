<?php

use App\Models\Contact\Gender;
use App\Models\Account\Account;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddGenderType extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('genders', function (Blueprint $table) {
            $table->char('type', 1)->after('name')->nullable();
        });

        $appLocale = config('app.locale');
        $womanEn = trans('app.gender_female', [], $appLocale);
        $manEn = trans('app.gender_male', [], $appLocale);
        $otherEn = trans('app.gender_none', [], $appLocale);

        Account::with(['users', 'genders'])->chunk(200, function ($accounts) use ($appLocale, $womanEn, $manEn, $otherEn) {
            foreach ($accounts as $account) {
                $locale = $account->getFirstLocale() ?: $appLocale;

                $woman = trans('app.gender_female', [], $locale);
                $man = trans('app.gender_male', [], $locale);
                $other = trans('app.gender_none', [], $locale);

                foreach ($account->genders->all() as $gender) {
                    if ($gender->name == $woman || $gender->name == $womanEn) {
                        $gender->type = Gender::FEMALE;
                    } elseif ($gender->name == $man || $gender->name == $manEn) {
                        $gender->type = Gender::MALE;
                    } elseif ($gender->name == $other || $gender->name == $otherEn) {
                        $gender->type = Gender::OTHER;
                    } else {
                        $gender->type = Gender::UNKNOWN;
                    }

                    // prevent timestamp update
                    $gender->timestamps = false;
                    $gender->save();
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('genders', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
}
